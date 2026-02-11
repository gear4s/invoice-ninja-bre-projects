<div class="w-full">
    @if($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        session()->forget('errors');
    @endphp

    @livewire($this->component, ['invitation_id' => $this->invitation_id, 'entity_type' => $entity_type, 'db' => $db, 'request_hash' => $request_hash], key($this->componentUniqueId()))

</div>

