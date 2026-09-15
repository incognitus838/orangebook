<div class="crest-panel">
    <h2 class="crest-panel-title">Notice board</h2>
    @forelse($notices as $notice)
        <article class="crest-notice">
            <p class="crest-notice-meta">{{ $notice->title }} · {{ date('d M Y', strtotime($notice->publish_date)) }} · {{ $notice->type == 'class' && $notice->standardLink ? $notice->standardLink->StandardSection : ucwords($notice->type) }}</p>
            <p class="crest-notice-body">{!! $notice->description !!}</p>
            <p class="crest-notice-time">{{ $notice->created_at->diffForHumans() }}</p>
        </article>
    @empty
        <p class="crest-empty">No notices yet.</p>
    @endforelse
</div>
