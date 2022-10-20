@component('mail::message')


{{$mailInfo['email_message']}}

@component('mail::button', ['url' => $mailInfo['url']])
Registration in duty!
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
