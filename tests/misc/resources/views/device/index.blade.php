@foreach ($sessions as $session)
    @lang('Created :date', ['date' => $session->created_at->fromNow()])
@endforeach
