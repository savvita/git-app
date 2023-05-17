@extends('layouts.app')

@section('head__title') Repositories @endsection

@section('content')
    @include('inc.php.functions')
    <h4 class="ms-2">Username: {{ $data['username'] }}</h4>
    @foreach($data['repo'] as $repo)
        <div class="card m-1">
            <div class="card-header"> {{ $repo->name }}</div>
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted m-0">Created at: {{ convert_date($repo->created_at) }}</p>
                    <p class="text-muted m-0">Language: {{ $repo->language }}</p>
                </div>
                <a href="{{ route('repo', ['username' => $data['username'], 'repoName' => $repo->name] ) }}" class="btn btn-primary px-3">View</a>
            </div>
        </div>
    @endforeach
@endsection
