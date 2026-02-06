<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PublicDocumentController extends Controller
{
    public function rentalChecklist(Request $request, Rental $rental)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $rental->load(['customer', 'items.productUnit.product', 'items.productUnit.kits', 'items.rentalItemKits.unitKit']);
        
        $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $rental]);
        
        return $pdf->stream('Checklist-' . $rental->rental_code . '.pdf');
    }

    public function rentalDeliveryNote(Request $request, Rental $rental)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $rental->load(['customer', 'items.productUnit.product', 'items.productUnit.kits']);
        
        $pdf = Pdf::loadView('pdf.delivery-note', ['rental' => $rental]);
        
        return $pdf->stream('DeliveryNote-' . $rental->rental_code . '.pdf');
    }

    public function quotation(Request $request, Quotation $quotation)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        foreach ($quotation->rentals as $rental) {
            foreach ($rental->items as $item) {
                $item->attachKitsFromUnit();
            }
        }

        $quotation->load(['customer', 'rentals.items.productUnit.product', 'rentals.items.rentalItemKits.unitKit']);
        
        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation]);
        
        return $pdf->stream('Quotation-' . $quotation->number . '.pdf');
    }

    public function invoice(Request $request, Invoice $invoice)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $invoice->load(['customer', 'rentals.items.productUnit.product']);
        
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        
        return $pdf->stream('Invoice-' . $invoice->number . '.pdf');
    }
}
