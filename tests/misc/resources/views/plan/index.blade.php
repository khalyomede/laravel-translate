@extends('layout.logged')

@section('content')
    <x-select id="placement" name="placement" required>
        <option value="free">
            @lang('Free')
            <span>test</span>
        </option>
        <option value="standard">
            @lang('Standard')
            <x-label :title="__('test')" />
        </option>
        <option value="premium">
            @choice('Premium')
        </option>
        <option>
            {{ __('test 2') }}
        </option>
        <option>
            {!! __('test 3') !!}
        </option>
    </x-select>
@endsection
