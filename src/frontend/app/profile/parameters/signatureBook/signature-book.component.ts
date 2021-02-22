import { Component, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';

@Component({
    selector: 'app-signature-book',
    templateUrl: './signature-book.component.html',
    styleUrls: ['./signature-book.component.scss'],
})

export class MySignatureBookComponent implements OnInit {
    
    @Input() signatureModel: any;
    @Input() userSignatures: any[];
    @Input() externalIdMaarchParapheur: any;
    @Input() loadingSign: boolean;

    highlightMe: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public functionsService: FunctionsService,
        public headerService: HeaderService,

    ){}
    
    ngOnInit(): void {}

    clickOnUploader(id: string) {
        $('#' + id).click();
    }

    uploadSignatureTrigger(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();

            this.signatureModel.name = fileInput.target.files[0].name;
            this.signatureModel.size = fileInput.target.files[0].size;
            this.signatureModel.type = fileInput.target.files[0].type;
            if (this.signatureModel.label == '') {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = (value: any) => {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };

        }
    }

    dndUploadSignature(event: any) {
        if (event.mouseEvent.dataTransfer.files && event.mouseEvent.dataTransfer.files[0]) {
            var reader = new FileReader();

            this.signatureModel.name = event.mouseEvent.dataTransfer.files[0].name;
            this.signatureModel.size = event.mouseEvent.dataTransfer.files[0].size;
            this.signatureModel.type = event.mouseEvent.dataTransfer.files[0].type;
            if (this.signatureModel.label == '') {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(event.mouseEvent.dataTransfer.files[0]);

            reader.onload = (value: any) => {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };
        }
    }

    submitSignature() {
        this.http.post('../rest/users/' + this.headerService.user.id + '/signatures', this.signatureModel)
            .subscribe((data: any) => {
                this.userSignatures = data.signatures;
                this.signatureModel = {
                    base64: '',
                    base64ForJs: '',
                    name: '',
                    type: '',
                    size: 0,
                    label: '',
                };
                this.notify.success(this.translate.instant('lang.signatureAdded'));
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updateSignature(signature: any) {
        this.http.put('../rest/users/' + this.headerService.user.id + '/signatures/' + signature.id, { 'label': signature.signature_label })
            .subscribe((data: any) => {
                this.notify.success(this.translate.instant('lang.signatureUpdated'));
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteSignature(id: number) {
        let r = confirm(this.translate.instant('lang.confirmDeleteSignature'));

        if (r) {
            this.http.delete('../rest/users/' + this.headerService.user.id + '/signatures/' + id)
                .subscribe((data: any) => {
                    this.headerService.user.signatures = data.signatures;
                    this.notify.success(this.translate.instant('lang.signatureDeleted'));
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    syncMP() {
        this.loadingSign = true;
        this.http.put('../rest/users/' + this.headerService.user.id + '/externalSignatures', {})
            .subscribe((data: any) => {
                this.loadingSign = false;
                this.notify.success(this.translate.instant('lang.signsSynchronized'));
            }, (err) => {
                this.loadingSign = false;
                this.notify.handleErrors(err);
            });
    }
    
}