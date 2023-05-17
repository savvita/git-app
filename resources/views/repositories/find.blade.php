@extends('layouts.app')

@section('head__title') Find Repositories @endsection

@section('content')
    <h3 class="text-center mb-4">Find repositories</h3>
    <form action="{{ route('repoList') }}" method="POST">
        @csrf
        <div class="d-flex">
            <input type="text" name="username" placeholder="Username" class="form-control flex-grow-1">
            <input type="submit" value="Search" class="btn btn-dark px-5 ms-2">
        </div>
    </form>
@endsection
