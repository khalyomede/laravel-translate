@extends('layout.guest')

@section('content')
    @lang($user->type)
    @lang(ucfirst($user->type))
@endsection
