<div class="card shadow rounded-0">
    <div class="card-header">Links</div>
    <div class="card-body">
        <p>Clone: {{ $data['repo']->clone_url }}</p>
        <p>SSH: {{ $data['repo']->ssh_url }}</p>
    </div>
</div>
