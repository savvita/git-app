<form method="post" action="{{ route('parentCommit', ['sha' => $data['obj']->commit->parents[0]->sha]) }}">
    @csrf
    <input type="hidden" name="username" value="{{ $data['username'] }}">
    <input type="hidden" name="repoName" value="{{ $data['repoName'] }}">
    <input type="hidden" name="branch" value="{{ $data['branch'] }}">
    <input type="hidden" name="filename" value="{{ $data['filename'] }}">
    <input type="submit" class="btn btn-primary" value="Get previous commit">
</form>
