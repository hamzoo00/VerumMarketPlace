@if ($errors->any())
    <div class="notice notice--bad" role="alert">
        <x-verum.icon name="info" />
        <div>
            <b>{{ trans_choice('There is a problem with your details.|There are :count problems with your details.', $errors->count()) }}</b>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
