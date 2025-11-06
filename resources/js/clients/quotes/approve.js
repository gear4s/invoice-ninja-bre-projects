/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license 
 */

class Approve {
    constructor(displaySignature, displayTerms, userInput, docuninjaActive) {
        this.shouldDisplaySignature = displaySignature;
        this.shouldDisplayTerms = displayTerms;
        this.shouldDisplayUserInput = userInput;
        this.termsAccepted = false;
        this.docuninjaActive = docuninjaActive;
    }

    submitForm() {
        document.getElementById('approve-form').submit();
    }

    displaySignature() {

        if(this.docuninjaActive){
            const pdfContainer = document.getElementById('pdf-slot-container');
            const docuninjaContainer = document.getElementById('docuninja-container');
            
            // Start transition: fade out PDF container
            pdfContainer.classList.add('opacity-0');
            
            // After fade out completes, hide PDF and show DocuNinja
            setTimeout(() => {
                // Hide PDF container completely
                pdfContainer.classList.add('hidden');
                pdfContainer.classList.remove('opacity-0');
                
                // Show DocuNinja container and prepare for fade in
                docuninjaContainer.classList.remove('hidden');
                // Force reflow to ensure hidden is removed before opacity transition
                docuninjaContainer.offsetHeight;
                
                // Start with opacity-0 and fade in
                docuninjaContainer.classList.add('opacity-0');
                // Trigger fade in after a tiny delay to ensure transition applies
                requestAnimationFrame(() => {
                    docuninjaContainer.classList.remove('opacity-0');
                });
            }, 500); // Wait for fade out to complete (matches duration-500)
            
            return;
        }

        let displaySignatureModal = document.getElementById(
            'displaySignatureModal'
        );
        displaySignatureModal.removeAttribute('style');

        // Ensure next step is disabled initially
        const nextStepButton = document.getElementById("signature-next-step");
        if (nextStepButton) {
            nextStepButton.disabled = true;
        }

        const signaturePad = new SignaturePad(
            document.getElementById('signature-pad'),
            {
                penColor: 'rgb(0, 0, 0)',
            }
        );

        signaturePad.onEnd = () => {  
            if (nextStepButton && !signaturePad.isEmpty()) {
                nextStepButton.disabled = false;
            }
        };

        signaturePad.onBegin = () => {
            if (nextStepButton) {
                nextStepButton.disabled = true;
            }
        };

        signaturePad.clear();

        this.signaturePad = signaturePad;
    }

    displayTerms() {
        let displayTermsModal = document.getElementById("displayTermsModal");
        displayTermsModal.removeAttribute("style");
    }

    displayInput() {
        let displayInputModal = document.getElementById("displayInputModal");
        displayInputModal.removeAttribute("style");
    }

    hideInput() {
        let displayInputModal = document.getElementById("displayInputModal");
        displayInputModal.style.display = 'none';
    }

    handle() {
        const approveButton = document.getElementById('approve-button');
        if (!approveButton) return;

        approveButton.addEventListener('click', () => {
            approveButton.disabled = true;
            
            // Re-enable the approve button after 2 seconds
            setTimeout(() => {
                approveButton.disabled = false;
            }, 2000);

            if (this.shouldDisplayUserInput) {
                this.displayInput();

                const inputNextStep = document.getElementById('input-next-step');
                if (!inputNextStep) return;

                inputNextStep.addEventListener('click', () => {
                    const userInput = document.getElementById('user_input');
                    if (!userInput) return;

                    document.querySelector(
                        'input[name="user_input"'
                    ).value = userInput.value;

                    this.hideInput();

                    if (this.shouldDisplaySignature && this.shouldDisplayTerms) {
                        this.displaySignature();

                        document
                            .getElementById('signature-next-step')
                            .addEventListener('click', () => {
                                if (!this.signaturePad.isEmpty()) {
                                    this.displayTerms();

                                    document
                                        .getElementById('accept-terms-button')
                                        .addEventListener('click', () => {
                                            document.querySelector(
                                                'input[name="signature"'
                                            ).value = this.signaturePad.toDataURL();
                                            this.termsAccepted = true;
                                            this.submitForm();
                                        });
                                }
                            });
                    } else if (this.shouldDisplaySignature) {
                        this.displaySignature();

                        document
                            .getElementById('signature-next-step')
                            .addEventListener('click', () => {
                                if (!this.signaturePad.isEmpty()) {
                                    document.querySelector(
                                        'input[name="signature"'
                                    ).value = this.signaturePad.toDataURL();
                                    this.submitForm();
                                }
                            });
                    } else if (this.shouldDisplayTerms) {
                        this.displayTerms();

                        document
                            .getElementById('accept-terms-button')
                            .addEventListener('click', () => {
                                this.termsAccepted = true;
                                this.submitForm();
                            });
                    } else {
                        this.submitForm();
                    }
                });
            } else if (this.shouldDisplaySignature && this.shouldDisplayTerms) {
                this.displaySignature();

                document
                    .getElementById('signature-next-step')
                    .addEventListener('click', () => {
                        if (!this.signaturePad.isEmpty()) {
                            this.displayTerms();

                            document
                                .getElementById('accept-terms-button')
                                .addEventListener('click', () => {
                                    document.querySelector(
                                        'input[name="signature"'
                                    ).value = this.signaturePad.toDataURL();
                                    this.termsAccepted = true;
                                    this.submitForm();
                                });
                        }
                    });
            } else if (this.shouldDisplaySignature) {
                this.displaySignature();

                document
                    .getElementById('signature-next-step')
                    .addEventListener('click', () => {
                        if (!this.signaturePad.isEmpty()) {
                            document.querySelector(
                                'input[name="signature"'
                            ).value = this.signaturePad.toDataURL();
                            this.submitForm();
                        }
                    });
            } else if (this.shouldDisplayTerms) {
                this.displayTerms();

                document
                    .getElementById('accept-terms-button')
                    .addEventListener('click', () => {
                        this.termsAccepted = true;
                        this.submitForm();
                    });
            } else {
                this.submitForm();
            }
        });
    }
}

const signature = document.querySelector('meta[name="require-quote-signature"]')
    .content;

const terms = document.querySelector('meta[name="show-quote-terms"]').content;

const user_input = document.querySelector('meta[name="accept-user-input"]').content;

const docuninja_active = document.querySelector('meta[name="docuninja-active"]').content;

new Approve(Boolean(+signature), Boolean(+terms), Boolean(+user_input), Boolean(+docuninja_active)).handle();
