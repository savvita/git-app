<select class="form-select" id="branch" data-username="{{ $data['username'] }}" data-repo="{{ $data['repo']->name }}">
    @foreach($data['repo']->branches as $branch)
        <option value="{{ $branch->commit->sha }}" {{ $branch->name == $data['repo']->default_branch ? 'selected' : '' }} data-last-commit-date="{{ $branch->commit->obj->commit->author->date }}" data-last-commit-message="{{ $branch->commit->obj->commit->message }}">
            {{ $branch->name }} {{ $branch->name == $data['repo']->default_branch ? '(default)' : '' }}
        </option>
    @endforeach
</select>
