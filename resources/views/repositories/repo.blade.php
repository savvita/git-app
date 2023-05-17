@extends('layouts.app')

@section('head__title') {{ $data['repo']->name }} @endsection

@section('content')
    @include('inc.php.functions')

    @include('inc.repo.header')

    <div class="row">
        <div class="col-lg-6 col-md-12">
            @include('inc.repo.repo_tree_table')
        </div>
        <div class="col-lg-6 col-md-12">
            @include('inc.repo.repo_links')
        </div>
    </div>

    <div>
        <h3 class="mt-3">Description</h3>
        {{ $data['repo']->description != '' ? $data['repo']->description : 'There is no description' }}
    </div>

@endsection
