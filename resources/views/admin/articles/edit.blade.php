@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', isset($manageJournal) ? 'Edit article | '.$manageJournal->title : 'Edit article | Admin')
@section('page_title', 'Edit article')
@section('page_subtitle', $article->title)

@section('page_actions')
    <a href="{{ isset($manageJournal) ? route('journal.manage.articles.index', $manageJournal) : route('admin.articles.index') }}" class="admin-btn admin-btn-secondary">Back to articles</a>
@endsection

@section('content')
    @include('admin.articles.form', ['article' => $article, 'ocrAvailable' => $ocrAvailable ?? false])
@endsection
