<div class="form-group">
    <label for="title">Title</label>
    <input type="text" class="form-control" name="title" id="title-input" value="{{$concept->title}}">
</div>

<div class="form-group">
    <label for="summary">Body</label>
    <textarea class="form-control" rows="10" name="body" id="body-input">{{$concept->body}}</textarea>
</div>


<div class="well">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#parents" aria-controls="parents" role="tab" data-toggle="tab">Parents & Tabs</a></li>
        <li role="presentation"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab">Summary</a></li>
        <li role="presentation"><a href="#source" aria-controls="source" role="tab" data-toggle="tab">Source</a></li>
        <li role="presentation"><a href="#image" aria-controls="image" role="tab" data-toggle="tab">Image</a></li>
        <li role="presentation"><a href="#task" aria-controls="task" role="tab" data-toggle="tab">Task</a></li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="parents">
            <div class="form-group">
                <label for="parent_id">Parent</label>
                <select style="width:100%" name="parent_id" id="parent-input" data-except="{{$concept->id}}">
                    @if ($concept->parent_id)
                        <option value="{{$concept->parent_id}}" selected="selected">{{$concept->parent->title}}</option>
                    @endif
                </select>
            </div>

            <div class="form-group">
                <label for="tags">Tags</label>
                <select style="width:100%" name="tags[]" id="tags-input" multiple="multiple">
                    @foreach ($concept->tags as $tag)
                        <option value="{{$tag->slug}}" selected="selected">{{$tag->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="summary">
            <div class="form-group">
                <label for="summary">Summary</label>
                <textarea class="form-control" rows="3" name="summary" id="summary-input">{{$concept->summary}}</textarea>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="source">
            <div class="form-group">
                <label for="title">Source URL</label>
                <input type="text" class="form-control" name="source_url" id="source_url-input" value="{{$concept->source_url}}">
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="image">

            <div class="row">
                <div class="col-md-6">
                @if (!empty($concept->image))
                    <img class="thumbnail" src="{{ url($picture->asset($concept->image, 'thumbnail')) }}">
                @endif
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="title">Image</label>
                        <input type="file" class="form-control" name="upload" id="upload-input">
                    </div>
                </div>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="task">
            <div class="row">

                <div class="col-md-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_task"> Is a task
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_done"> Is done
                        </label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="due_at">Due at</label>
                        <input type="datetime-local" class="form-control" name="due_at" id="due_at-input">
                    </div>
                    <div class="form-group">
                        <label for="remind_at">Remind at</label>
                        <input type="datetime-local" class="form-control" name="remind_at" id="remind_at-input">
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

