@extends('layouts.app')

@section('head__title') Error @endsection

@section('content')
    <div class="alert alert-danger">{{$error}}</div>
@endsection
