<div class="blog-post">
    @if ($concept->image)
        <a href="/{{$concept->slug}}/">
            <img class="blog-post-thumbnail thumbnail" src="{{$concept->image}}">
        </a>
    @endif
    <h2 class="blog-post-title"><a href="{{$url_prefix}}/{{$concept->slug}}/">{{$concept->title}}</a></h2>
    <p class="blog-post-meta">{{ strftime('%Y-%m-%d', strtotime($concept->created_at)) }} by {{$concept->owner->name}}</p>

    <p>{{$concept->summary}}</p>
</div>
