<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Livewire\Flow2;

use Livewire\Component;

class DocuNinja extends Component
{
    // Properties to store DocuNinja internal state
    public $docuNinjaCredentials = [];
    public $docuNinjaFormData = [];
    public $docuNinjaSignatureData = [];
    public $docuNinjaSigningStatus = 'unknown';
    public $docuNinjaInternalState = [];
    
    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return render('flow2.docu-ninja', [
            'token' => '',
            'document' => '01K3SJ59KNWZX6DBM38RKTCQNJ',
            'invitation' => '01K3SJ5T76BCSBA9Y2PQJXFFG7',
            'sig' => 'eyJpdiI6IkxWYVVMbDZBQ21EODZhQ0E4YnlxSGc9PSIsInZhbHVlIjoiZlZvYmpkQ3BIaXhHVis3U1FuWW05M2pYYlJlcnRQL05jakxHNFl3RnZFeTAzUWM2N0xvclJjdk5oQmZxZFBQSSIsIm1hYyI6IjNmY2RlZTdjZjQyZjFlYWQ0NTI5YzI5ZDhkZTBmM2FhNjcyMmZhZGRhNjVjMjdkYmNkMmQwYjZkODU2NGNkMTciLCJ0YWciOiIifQ%3D%3D.'
        ]);
    }

    /**
     * Handle DocuNinja state changes from JavaScript
     * This method receives the internal variables and state from the DocuNinja component
     */
    public function onDocuNinjaStateChange($data)
    {
        // Store the received data in component properties
        $this->docuNinjaCredentials = $data['credentials'] ?? [];
        $this->docuNinjaFormData = $data['formData'] ?? [];
        $this->docuNinjaSignatureData = $data['signatureData'] ?? [];
        $this->docuNinjaSigningStatus = $data['signingStatus'] ?? 'unknown';
        $this->docuNinjaInternalState = $data['fullState'] ?? [];
        
        // Log the received data for debugging
        \Log::info('DocuNinja state change received', $data);
        
        // You can now use these variables in your component
        $this->dispatch('docuNinjaStateUpdated', [
            'credentials' => $this->docuNinjaCredentials,
            'formData' => $this->docuNinjaFormData,
            'signatureData' => $this->docuNinjaSignatureData,
            'signingStatus' => $this->docuNinjaSigningStatus,
            'internalState' => $this->docuNinjaInternalState
        ]);
    }
    
    /**
     * Get the current DocuNinja credentials
     */
    public function getDocuNinjaCredentials()
    {
        return $this->docuNinjaCredentials;
    }
    
    /**
     * Get the current DocuNinja form data
     */
    public function getDocuNinjaFormData()
    {
        return $this->docuNinjaFormData;
    }
    
    /**
     * Get the current DocuNinja signature data
     */
    public function getDocuNinjaSignatureData()
    {
        return $this->docuNinjaSignatureData;
    }
    
    /**
     * Get the current DocuNinja signing status
     */
    public function getDocuNinjaSigningStatus()
    {
        return $this->docuNinjaSigningStatus;
    }
    
    /**
     * Get the complete DocuNinja internal state
     */
    public function getDocuNinjaInternalState()
    {
        return $this->docuNinjaInternalState;
    }
    
    /**
     * Check if DocuNinja has specific credentials
     */
    public function hasDocuNinjaCredentials()
    {
        return !empty($this->docuNinjaCredentials);
    }
    
    /**
     * Check if DocuNinja has form data
     */
    public function hasDocuNinjaFormData()
    {
        return !empty($this->docuNinjaFormData);
    }
    
    /**
     * Check if DocuNinja has signature data
     */
    public function hasDocuNinjaSignatureData()
    {
        return !empty($this->docuNinjaSignatureData);
    }
    
    /**
     * Get a specific value from DocuNinja form data
     */
    public function getDocuNinjaFormValue($key, $default = null)
    {
        return $this->docuNinjaFormData[$key] ?? $default;
    }
    
    /**
     * Get a specific value from DocuNinja credentials
     */
    public function getDocuNinjaCredential($key, $default = null)
    {
        return $this->docuNinjaCredentials[$key] ?? $default;
    }
    
    /**
     * Check if DocuNinja is in a specific signing status
     */
    public function isDocuNinjaStatus($status)
    {
        return $this->docuNinjaSigningStatus === $status;
    }
    
    /**
     * Check if DocuNinja is ready for signing
     */
    public function isDocuNinjaReady()
    {
        return $this->isDocuNinjaStatus('ready') || $this->isDocuNinjaStatus('initialized');
    }
    
    /**
     * Check if DocuNinja is currently signing
     */
    public function isDocuNinjaSigning()
    {
        return $this->isDocuNinjaStatus('signing') || $this->isDocuNinjaStatus('in_progress');
    }
    
    /**
     * Check if DocuNinja has completed signing
     */
    public function isDocuNinjaCompleted()
    {
        return $this->isDocuNinjaStatus('completed') || $this->isDocuNinjaStatus('finished');
    }
    
    /**
     * Refresh the DocuNinja state (useful for debugging)
     */
    public function refreshDocuNinjaState()
    {
        // This method can be called to manually refresh the state
        // The actual state will be updated via JavaScript events
        $this->dispatch('docuNinjaStateRefreshed', [
            'timestamp' => now(),
            'currentState' => [
                'credentials' => $this->docuNinjaCredentials,
                'formData' => $this->docuNinjaFormData,
                'signatureData' => $this->docuNinjaSignatureData,
                'signingStatus' => $this->docuNinjaSigningStatus,
                'internalState' => $this->docuNinjaInternalState
            ]
        ]);
        
        // Log the refresh action
        \Log::info('DocuNinja state refresh requested');
    }
    
    /**
     * Clear the DocuNinja state
     */
    public function clearDocuNinjaState()
    {
        $this->docuNinjaCredentials = [];
        $this->docuNinjaFormData = [];
        $this->docuNinjaSignatureData = [];
        $this->docuNinjaSigningStatus = 'unknown';
        $this->docuNinjaInternalState = [];
        
        // Dispatch event to notify that state was cleared
        $this->dispatch('docuNinjaStateCleared');
        
        // Log the clear action
        \Log::info('DocuNinja state cleared');
    }

    public function exception($e, $stopPropagation)
    {
        app('sentry')->captureException($e);
        nlog($e->getMessage());
        $stopPropagation();
    }
}



