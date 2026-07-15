@extends('admin.layouts.app', ['title' => 'LMS Module Management | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Learning system - LMS Modules</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Module management</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Add a module on behalf of a trainer, assign it to a batch, or remove an outdated learning file.</p>
    </header>

    <details class="dashboard-panel" @if($errors->hasAny(['trainer_id', 'training_batch_id', 'title', 'description', 'module_file'])) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-900">
            <span>Add a learning module</span>
            <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Admin action</span>
        </summary>
        <form method="POST" action="{{ route('admin.learning.modules.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @csrf
            <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer</label><select name="trainer_id" class="form-field" required><option value="">Select trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}" @selected((int)old('trainer_id') === $trainer->id)>{{ $trainer->name }} · {{ $trainer->email }}</option>@endforeach</select>@error('trainer_id')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label><select name="training_batch_id" class="form-field" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)old('training_batch_id') === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select>@error('training_batch_id')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Title</label><input name="title" value="{{ old('title') }}" class="form-field" required>@error('title')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2 xl:col-span-3"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Description</label><textarea name="description" rows="3" class="form-field" required>{{ old('description') }}</textarea>@error('description')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">PDF, image, or video</label><input name="module_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.mp4,.webm" class="form-field" required>@error('module_file')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror</div>
            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700"><input type="hidden" name="is_published" value="0"><input name="is_published" type="checkbox" value="1" @checked(old('is_published', true))> Publish immediately</label>
            <div class="md:col-span-2 xl:col-span-3"><button class="primary-action">Add module</button></div>
        </form>
    </details>

    <form method="GET" data-auto-filter class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search module or trainer</label><input name="search" value="{{ $filters['search'] ?? '' }}" class="form-field"></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Publication</label><select name="published" class="form-field"><option value="">All states</option><option value="yes" @selected(($filters['published'] ?? '') === 'yes')>Published</option><option value="no" @selected(($filters['published'] ?? '') === 'no')>Draft</option></select></div>
        <div class="flex gap-2 md:col-span-4"><button class="primary-action">Filter modules</button><a href="{{ route('admin.learning.modules') }}" class="secondary-action">Reset</a></div>
    </form>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table min-w-[64rem]">
            <thead><tr><th>Module</th><th>Trainer</th><th>Batch</th><th>File</th><th>Publication</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($modules as $module)
                    <tr>
                        <td><p class="font-bold text-slate-950">{{ $module->title }}</p><p class="mt-1 max-w-md text-xs line-clamp-2">{{ $module->description }}</p></td>
                        <td>{{ $module->trainer?->name ?? 'Unassigned' }}</td>
                        <td>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General' }}</td>
                        <td><span class="break-all text-xs">{{ $module->original_file_name }}</span></td>
                        <td><span class="dashboard-pill {{ $module->is_published ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $module->is_published ? 'Published' : 'Draft' }}</span><p class="mt-2 text-xs">{{ $module->published_at?->format('M d, Y g:i A') ?? 'Not published' }}</p></td>
                        <td><form method="POST" action="{{ route('admin.learning.modules.destroy', $module) }}" onsubmit="return confirm('Remove this module and its recorded progress?')">@csrf @method('DELETE')<button class="min-h-10 rounded-lg border border-red-200 bg-white px-3 text-xs font-bold text-red-700 hover:bg-red-50">Remove</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-14 text-center">No modules match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($modules->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $modules->links() }}</div>@endif
    </div>
</section>
@endsection
