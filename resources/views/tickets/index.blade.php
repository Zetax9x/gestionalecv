@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Elenco Ticket')

@section('page-actions')
<a href="{{ route('tickets.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle"></i> Nuovo Ticket
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        @include('tickets.partials.table', ['tickets' => $tickets])
    </div>
    <div class="card-footer">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
