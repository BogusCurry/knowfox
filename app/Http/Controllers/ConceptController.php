<?php

namespace Knowfox\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Knowfox\Http\Requests\ConceptRequest;
use Knowfox\Models\Concept;
use Illuminate\Http\Request;
use Knowfox\Services\PictureService;
use Validator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\File;

class ConceptController extends Controller
{
    private static $validateImageRules = [
        'upload' => 'sometimes|image|mimes:jpeg,png|min:1|max:10000',
    ];

    public function toplevel(Request $request)
    {
        return $this->index($request, 'toplevel');
    }

    public function flagged(Request $request)
    {
        return $this->index($request, 'flagged');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $special = false)
    {
        $concepts = Concept::withDepth()
            ->with('tagged')
            ->where('owner_id', Auth::id())
            ->orderBy('updated_at', "desc");

        $page_title = '';

        if ($special) {

            switch ($special) {
                case 'flagged':
                    $page_title = 'Flagged concepts';
                    $concepts->where('is_flagged', 1);
                    break;
                case 'toplevel':
                    $page_title = 'Toplevel concepts';
                    $concepts->whereIsRoot();
                    break;
            }
        }

        if ($request->has('tag')) {
            $concepts->withAllTags([$request->input('tag')]);
            $page_title .= ' with tag "' . $request->input('tag') . '"';
        }

        $search_term = '';

        // https://dev.mysql.com/doc/refman/5.7/en/fulltext-query-expansion.html

        if ($request->has('q')) {
            $search_term = $request->input('q');
            $concepts->where('title', 'like', $search_term . '%');
            $concepts->orWhereRaw(
                'MATCH(title,summary,body) AGAINST(? IN NATURAL LANGUAGE MODE)', [$search_term]
            );
        }

        // jquery-ui.autocomplete
        if ($request->has('term')) {
            $search_term = $request->input('term');
            $concepts->where('title', 'like', $search_term . '%');
            $concepts->orWhereRaw(
                'MATCH(title,summary,body) AGAINST(? IN NATURAL LANGUAGE MODE)', [$search_term]
            );
        }

        if ($request->has('except')) {
            $concepts->where('id', '!=', $request->input('except'));
        }

        if ($request->has('limit')) {
            $concepts->limit((int)$request->input('limit'));
        }

        if ($request->format() == 'json') {
            $items = $concepts
                ->select('id', 'title')
                ->paginate();
            return response()->json($items);
        }
        else {
            $result = $concepts->paginate();
            $page_title = 'Concept' . ($result->total() != 1 ? 's' : '') . $page_title;

            return view('concept.index', [
                'concepts' => $result,
                'page_title' => $page_title,
                'sub_title' => $result->firstItem() . ' &hellip; ' . $result->lastItem() . ' of ' . $result->total(),
                'search_term' => $search_term,
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $concept = new Concept([
            'weight' => 0,
            'uuid' => Uuid::uuid1()->toString(),
        ]);

        if ($request->has('parent_id')) {
            $parent = Concept::findOrFail($request->input('parent_id'));
            $this->authorize('view', $parent);
            $concept->appendToNode($parent);
        }

        return view('concept.create', [
            'concept' => $concept,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ConceptRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConceptRequest $request)
    {
        $concept = new Concept($request->all());
        $concept->owner_id = $request->user()->id;
        $concept->save();

        if ($request->has('tags')) {
            $concept->tag($request->input('tags'));
        }
        else {
            $concept->untag();
        }

        return redirect()->route('concept.show', [$concept])
            ->with('status', 'Concept created');
    }

    /**
     * Display the specified resource.
     *
     * @param  Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function show(Concept $concept)
    {
        $this->authorize('view', $concept);

        $concept->load('related', 'inverseRelated', 'tagged');

        return view('concept.show', [
            'page_title' => $concept->title,
            'concept' => $concept,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function opml(Concept $concept)
    {
        $this->authorize('view', $concept);

        $concept->load('descendants');

        $traverse = function ($tree) use (&$traverse) {

            $concepts = [];
            foreach ($tree as $concept) {
                $concepts[] = view('partials.outline', [
                    'concept' => $concept,
                    'descendants' => $traverse($concept->children),
                ]);
            }
            return join("\n", $concepts);
        };

        /*
        $concepts = \Kalnoy\Nestedset\Collection::make()
            ->add($concept)
            ->toTree();
        */


        return response(
            view('concept.opml', [
                'concept' => $concept,
                'tree' => view('partials.outline', [
                    'concept' => $concept,
                    'descendants' => $traverse($concept->descendants->toTree()),
                ]),
            ]), 200)
            ->header('Content-type', 'text/x-opml');
    }

    /**
     * Display the specified resource using Graphviz.
     *
     * @param  Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function outline(Concept $concept)
    {
        $this->authorize('view', $concept);

        $concept->load('related', 'inverseRelated', 'tagged');

        return view('concept.outline', [
            'page_title' => $concept->title,
            'concept' => $concept,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Knowfox\Models\Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function edit(Concept $concept)
    {
        $this->authorize('update', $concept);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ConceptRequest  $request
     * @param  Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function update(PictureService $picture, ConceptRequest $request, Concept $concept)
    {
        $concept->fill($request->all());

        if (!$request->has('parent_id')) {
            $concept->makeRoot();
        }

        $concept->is_flagged = $request->has('is_flagged');

        $concept->save();

        if ($request->has('tags')) {
            $concept->retag($request->input('tags'));
        }
        else {
            $concept->untag();
        }

        $filename = '';
        if ($request->hasFile('upload')) {
            $filename = $picture->handleUpload(
                $request->file('upload'),
                self::PICTURES_DIR
            );
            $concept->image = $filename;
            $concept->save();
        }
        return redirect()->route('concept.show', [$concept])
            ->with('status', 'Concept updated ' . $filename);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Knowfox\Models\Concept  $concept
     * @return \Illuminate\Http\Response
     */
    public function destroy(Concept $concept)
    {
        $this->authorize('delete', $concept);

        $title = "#{$concept->id} \"{$concept->title}\"";
        $parent_id = $concept->getParentId();

        $concept->delete();

        if ($parent_id) {
            return redirect()->route('concept.show', [$parent_id])
                ->with('status', 'Concept ' . $title . ' deleted');
        }
        else {
            return redirect()->route('concept.index')
                ->with('status', 'Concept ' . $title . ' deleted');
        }
    }

    public function image(PictureService $picture, Request $request, Concept $concept, $filename)
    {
        $this->authorize('view', $concept);

        $style = $request->has('style') ? $request->input('style') : 'original';
        return $picture->image($concept->uuid, $filename, $style);
    }

    public function upload(PictureService $picture, Request $request, $uuid)
    {
        $concept = Concept::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $concept);

        $path = $picture->upload($request->file('file'), $uuid);

        $file = new File($path);
        if (strpos($file->getMimeType(), 'image/') === 0) {
            $parts = pathinfo($path);
            $concept->body .= "\n![{$parts['filename']}]({$parts['basename']})\n";
            $concept->save();
        }

        return response()->json(['success' => $path]);
    }

    public function images(PictureService $picture, Concept $concept)
    {
        $this->authorize('view', $concept);
        return response()->json($picture->images($concept->uuid));
    }
}
