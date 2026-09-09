<?php

namespace App\Http\Controllers;

use App\Models\Piutang;
use Illuminate\Http\Request;

class PiutangController extends Controller
{
    public function index()
    {
        $Piutang = Piutang::all();

        return view('Piutang.index', compact('Piutang'));
    }

    public function show($PiutangID)
    {
        $Piutang = Piutang::findOrFail($PiutangID);

        return view('Piutang.show', compact('Piutang'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'JwbKasusID' => 'required|integer',
        ]);

        $Piutang = Piutang::create($validated);

        return redirect()
            ->route('Piutang.show', $Piutang->PiutangID)
            ->with('success', 'Data Piutang berhasil dibuat.');
    }
}