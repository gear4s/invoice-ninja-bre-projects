<?php

namespace App\Livewire\Flow2;

use App\DataMapper\InvoiceSync;
use App\Models\InvoiceInvitation;
use App\Libraries\MultiDB;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Utils\Traits\WithSecureContext;
use Livewire\Attributes\Lazy;

class DocuNinjaLoader extends Component
{
    use WithSecureContext;

    public $invitation_id;
    public $isLoading = true;
    public $error = null;
    public $isReady = false;

    public function mount()
    {
        // Set database context
        MultiDB::setDb($this->getContext()['db']);   
     }

    public function loadDocuNinjaData()
    {
        try {
            
            $invitation = InvoiceInvitation::find($this->invitation_id);
            
            if (!$invitation) {
                throw new \Exception('Invoice invitation not found');
            }

            // Check if DocuNinja is already completed
            if(isset($invitation->invoice->sync->dn_completed) && $invitation->invoice->sync->dn_completed){
                $signable = [
                    'document_id' => $invitation->invoice->sync->dn_id,
                    'document_invitation_id' => $invitation->invoice->sync->dn_invitation_id,
                    'sig' => $invitation->invoice->sync->dn_sig,
                    'success' => true,
                ];
                
            }
            elseif(isset($invitation->invoice->sync) && 
                isset($invitation->invoice->sync->dn_sig) &&
                isset($invitation->invoice->sync->dn_invitation_id) &&
                (stripos($invitation->invoice->sync->dn_contacts, $invitation->contact->contact_key) !== false) && //  make sure the current contact matches the existing document!
                isset($invitation->invoice->sync->dn_id)){
                
                $signable = [
                    'document_id' => $invitation->invoice->sync->dn_id,
                    'document_invitation_id' => $invitation->invoice->sync->dn_invitation_id,
                    'sig' => $invitation->invoice->sync->dn_sig,
                    'success' => !$invitation->invoice->sync->dn_completed,
                ];
                
            }
            // Generate new DocuNinja signable data
            else{
                
                $signable = $invitation->invoice->service()->getDocuNinjaSignable($invitation);
                
                $sync = InvoiceSync::fromArray([
                    'dn_id' => $signable['document_id'],
                    'dn_invitation_id' => $signable['document_invitation_id'],
                    'dn_sig' => $signable['sig'],
                    'dn_completed' => false,
                    'dn_contacts' => $invitation->contact->contact_key,
                ]);
                $invitation->invoice->sync = $sync;
                $invitation->invoice->save();
                
            }

            // Check if signing is not successful (already completed or error)
            if(!$signable['success']){
                $this->dispatch('docuninja-signature-captured');
                return;
            }
            
            // Mark as ready and dispatch event to parent
            $this->isReady = true;
            $this->isLoading = false;
            
            // Dispatch event to InvoicePay to switch to DocuNinja component
            $this->dispatch('docuninja-loader-ready', [
                'invitation_id' => $this->invitation_id,
                'document_id' => $signable['document_id'],
                'document_invitation_id' => $signable['document_invitation_id'],
                'sig' => $signable['sig'],
                'company_key' => $invitation->company->company_key
            ]);

        } catch (\Exception $e) {
                      
            $this->error = 'Failed to load DocuNinja data: ' . $e->getMessage();
            $this->isLoading = false;
        }
    }

    // Method to retry loading if there was an error
    public function retryLoading()
    {
        $this->error = null;
        $this->isLoading = true;
        $this->isReady = false;
        $this->loadingMessage = 'Retrying DocuNinja initialization...';
        
        $this->loadDocuNinjaData();
    }

    public function render()
    {
        \Log::info('DocuNinjaLoader render called', [
            'isLoading' => $this->isLoading,
            'isReady' => $this->isReady,
            'error' => $this->error
        ]);

        return view('portal.ninja2020.flow2.docu-ninja-loader');
    }
}
