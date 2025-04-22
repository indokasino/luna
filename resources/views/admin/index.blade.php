@extends('admin.layout')

@section('title', 'Dashboard - Webhook GPT')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            <i class="fas fa-list me-2"></i> Daftar Pertanyaan & Jawaban
                        </h6>
                    </div>
                </div>
                
                <div class="card-body px-0 pb-2">
                    <div class="px-4 py-2 d-flex justify-content-between align-items-center">
                        <form class="d-flex" action="{{ route('qna.index') }}" method="GET">
                            <div class="input-group input-group-outline me-2" style="width: 230px;">
                                <label class="form-label">Cari pertanyaan/jawaban...</label>
                                <input type="text" name="search" class="form-control" value="{{ request('search') }}">
                            </div>
                            
                            <div class="input-group input-group-static me-2" style="width: 150px;">
                                <label for="status" class="ms-0">Status</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Semua</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-info mb-0">
                                <i class="fas fa-search me-2"></i> Filter
                            </button>
                        </form>
                        
                        <a href="{{ route('qna.create') }}" class="btn btn-success mb-0">
                            <i class="fas fa-plus me-2"></i> Tambah QnA
                        </a>
                    </div>
                    
                    @if(session('success'))
                    <div class="alert alert-success mx-4 mt-2">
                        {{ session('success') }}
                    </div>
                    @endif
                    
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pertanyaan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Confidence</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tags</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Terakhir Diperbarui</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($qnas as $qna)
                                <tr>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0 px-3">{{ $qna->id }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">
                                            {{ Str::limit($qna->question, 80) }}
                                        </p>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-gradient-{{ $qna->status === 'active' ? 'success' : ($qna->status === 'inactive' ? 'secondary' : 'warning') }}">
                                            {{ ucfirst($qna->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ number_format($qna->confidence_score, 2) }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $qna->tags ?? '-' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $qna->updated_at->format('d M Y H:i') }}</p>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('qna.edit', $qna) }}" class="btn btn-sm btn-info mb-0" data-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('qna.destroy', $qna) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger mb-0" data-toggle="tooltip" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-sm mb-0">Tidak ada data yang ditemukan</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-4 py-3">
                        {{ $qnas->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection