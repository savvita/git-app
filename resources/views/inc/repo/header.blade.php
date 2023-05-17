<h4 class="mb-0">{{ $data['repo']->full_name }}
    (<a href="{{ $data['repo']->html_url }}" class="small">GitHub</a>)
    <span class="badge bg-primary fs-6">{{ $data['repo']->private ? 'Private' : 'Public' }}</span>
</h4>
<p class="text-muted m-0 small">Created at: {{ convert_date($data['repo']->created_at) }}</p>
<p class="text-muted small m-0">Language: {{ $data['repo']->language }}</p>
<p class="text-muted small">Owner: {{ $data['repo']->owner->login }} (<a href="{{ $data['repo']->owner->html_url }}">GitHub</a>)</p>
<div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        @include('inc.repo.branch_select')
        @include('inc.repo.branch_count')
    </div>
    <a id="downloadRepo" href="https://api.github.com/repos/{{$data['username'] }}/{{ $data['repo']->name }}/zipball/" class="btn btn-success">Download</a>
</div>
<p id="last_commit" class="text-muted"></p>
