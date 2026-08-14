@extends('layouts.admin')

@section('title', 'Create journal | Admin')
@section('page_title', 'Create journal')
@section('page_subtitle', 'Add a journal and set its public theme')

@section('content')
    @include('admin.journals.form', [
        'journal' => $journal ?? new \App\Models\Journal,
        'theme' => $theme ?? \App\Support\JournalTheme::DEFAULTS,
    ])
@endsection
