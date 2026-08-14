@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', isset($manageJournal) ? 'Create article | '.$manageJournal->title : 'Create article | Admin')
@section('page_title', 'Create article')
@section('page_subtitle', isset($manageJournal) ? $manageJournal->title : 'Fill details manually or extract them from a document')

@section('page_actions')
    <a href="{{ isset($manageJournal) ? route('journal.manage.articles.index', $manageJournal) : route('admin.articles.index') }}" class="admin-btn admin-btn-secondary">Back to articles</a>
@endsection

@section('content')
    @include('admin.articles.form')
@endsection
