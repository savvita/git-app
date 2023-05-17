@extends('layouts.app')

@section('head__title') {{ $data['filename'] }} @endsection

@section('content')
    @include('inc.php.functions')
    <?php
        $file = null;

        if($data['obj']->commit != null) {
            foreach ($data['obj']->commit->files as $obj) {
                if (str_ends_with($obj->filename, $data['filename'])) {
                    $file = $obj;
                    break;
                }
            }
        }

        if($file == null) {
            die("File not found");
        }
    ?>
    <h3 class="mb-0">{{ $data['filename'] }}</h3>
    <p class="text-muted mt-0 small text-nowrap">Size: {{ $data['obj']->size }}&nbsp;B</p>
    @if($data['obj']->commit != null)
        <div class="d-flex justify-content-between flex-wrap align-items-center">
            <p>Committed at {{ convert_date($data['obj']->commit->commit->author->date) }} by {{ $data['obj']->commit->commit->author->name }} ({{ $data['obj']->commit->commit->message }})</p>

            @if(count($data['obj']->commit->parents) > 0 && $file->status != 'added')
                @include('inc.repo.prev_commit_button')
            @endif
        </div>
    @endif
    <hr />
    <div class="card">
        <div class="card-header">
            <p>Status: {{$file->status}}</p>
            <p>Changes: {{$file->changes}} (added: {{$file->additions}}, deleted: {{$file->deletions}})</p>
        </div>
        <div class="card-body">
            <pre data-patch="{{$file->patch}}">
                {{ $data['obj']->content }}
            </pre>
        </div>
    </div>
@endsection
