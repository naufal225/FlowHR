<?php

namespace App\Http\Controllers\AdminController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReimbursementType;
use Illuminate\Support\Facades\Validator;

class ReimbursementTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $types = ReimbursementType::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })
            ->latest()
            ->paginate(10);

        $totalTypes = ReimbursementType::count(); // Untuk mengecek apakah ada data di database

        return view('admin.reimbursement-types.index', compact('types', 'search', 'totalTypes'));
    }

    public function show(ReimbursementType $reimbursementType)
    {
        return view('admin.reimbursement-types.show', compact('reimbursementType'));
    }

    public function create()
    {
        return view('admin.reimbursement-types.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:reimbursement_types,name',
        ], [
            'name.required' => 'Nama tipe reimbursement wajib diisi.',
            'name.unique' => 'Tipe reimbursement dengan nama ini sudah ada.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        ReimbursementType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.reimbursement-types.index')
            ->with('success', 'Tipe reimbursement berhasil ditambahkan.');
    }

    public function edit(ReimbursementType $reimbursementType)
    {
        return view('admin.reimbursement-types.edit', compact('reimbursementType'));
    }

    public function update(Request $request, ReimbursementType $reimbursementType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:reimbursement_types,name,' . $reimbursementType->id,
        ], [
            'name.required' => 'Nama tipe reimbursement wajib diisi.',
            'name.unique' => 'Tipe reimbursement dengan nama ini sudah ada.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $reimbursementType->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.reimbursement-types.index')
            ->with('success', 'Tipe reimbursement berhasil diperbarui.');
    }

    public function destroy(ReimbursementType $reimbursementType)
    {
        // Cek apakah tipe ini sedang digunakan
        if ($reimbursementType->reimbursements()->count() > 0) {
            return redirect()->route('admin.reimbursement-types.index')
                ->with('error', 'Tidak bisa menghapus tipe yang sedang digunakan dalam pengajuan reimbursement.');
        }

        $reimbursementType->delete();

        return redirect()->route('admin.reimbursement-types.index')
            ->with('success', 'Tipe reimbursement berhasil dihapus.');
    }
}
