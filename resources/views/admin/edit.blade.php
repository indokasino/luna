@extends('admin.layout')

@section('title', isset($qna) ? 'Edit Data QnA - Webhook GPT' : 'Tambah Data QnA - Webhook GPT')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3">
                            <i class="fas fa-{{ isset($qna) ? 'edit' : 'plus' }} me-2"></i> 
                            {{ isset($qna) ? 'Edit Data QnA' : 'Tambah Data QnA' }}
                        </h6>
                        <a href="{{ route('qna.index') }}" class="btn btn-sm btn-info mx-3">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <form action="{{ isset($qna) ? route('qna.update', $qna) : route('qna.store') }}" method="POST">
                        @csrf
                        @if(isset($qna))
                            @method('PUT')
                        @endif
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group input-group-static mb-4">
                                    <label for="question">Pertanyaan <span class="text-danger">*</span></label>
                                    <textarea name="question" id="question" class="form-control" rows="3" required>{{ old('question', $qna->question ?? '') }}</textarea>
                                    @error('question')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="input-group input-group-static mb-4">
                                    <label for="answer">Jawaban <span class="text-danger">*</span></label>
                                    <textarea name="answer" id="answer" class="form-control" rows="6" required>{{ old('answer', $qna->answer ?? '') }}</textarea>
                                    @error('answer')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="input-group input-group-static mb-4">
                                    <label for="tags">Tags</label>
                                    <input type="text" name="tags" id="tags" class="form-control" value="{{ old('tags', $qna->tags ?? '') }}">
                                    <small class="form-text text-muted">Pisahkan dengan koma (misal: login, deposit, bonus)</small>
                                    @error('tags')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="input-group input-group-static mb-4">
                                    <label for="confidence_score">Confidence Score</label>
                                    <input type="number" name="confidence_score" id="confidence_score" class="form-control" min="0" max="1" step="0.01" value="{{ old('confidence_score', $qna->confidence_score ?? '1.0') }}">
                                    <small class="form-text text-muted">Nilai antara 0 - 1</small>
                                    @error('confidence_score')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="input-group input-group-static mb-4">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="active" {{ old('status', $qna->status ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                                        <option value="inactive" {{ old('status', $qna->status ?? '') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                        <option value="draft" {{ old('status', $qna->status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                                    </select>
                                    @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('qna.index') }}" class="btn btn-light me-2">
                                Batal
                            </a>
                            <button type="submit" class="btn bg-gradient-primary">
                                <i class="fas fa-save me-2"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(event) {
            let hasError = false;
            const requiredInputs = form.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    hasError = true;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            const confidenceScoreInput = document.getElementById('confidence_score');
            const confidenceScore = parseFloat(confidenceScoreInput.value);
            
            if (isNaN(confidenceScore) || confidenceScore < 0 || confidenceScore > 1) {
                hasError = true;
                confidenceScoreInput.classList.add('is-invalid');
                
                // Display error message
                let errorDiv = confidenceScoreInput.nextElementSibling.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('text-danger')) {
                    errorDiv = document.createElement('div');
                    errorDiv.classList.add('text-danger');
                    confidenceScoreInput.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = 'Nilai confidence score harus antara 0 dan 1';
            }
            
            if (hasError) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush