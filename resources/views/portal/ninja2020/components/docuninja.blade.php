@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.sign_now'))

@push('head')
<link rel="stylesheet" href="{{ asset('build/dist/builder2.0.standalone.css/builder2.0.standalone.css') }}">
@endpush

@section('body')
    @livewire('sign', ['invitation_id' => $invitation_id, 'entity_type' => $entity_type, 'db' => $db, 'request_hash' => $request_hash])
@endsection