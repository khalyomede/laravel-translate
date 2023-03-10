@extends('layout')

@section('content')
    @lang('List of books')

    {{ __('Welcome to the list of books.') }}

    {{ trans('This list shows an excerpt of each books.') }}

    {{ trans_choice(':count books displayed.', 1, ['count' => 1]) }}

    @choice(':count authors found.')
@endsection
