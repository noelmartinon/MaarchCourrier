import { Component, OnInit, NgZone, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from './translate.component';
import { NotificationService } from './notification.service';
import { HeaderService } from '../service/header.service';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../service/app.service';

declare var $: any;

@Component({
    templateUrl: 'save-numeric-package.component.html',
    styleUrls: ['profile.component.css'],
    providers: [NotificationService, AppService]
})
export class SaveNumericPackageComponent implements OnInit {

    lang: any = LANG;

    numericPackage: any = {
        base64: '',
        name: '',
        type: '',
        size: 0,
        label: '',
        extension: '',
    };

    loading: boolean = false;

    @ViewChild('snav', { static: true }) sidenavLeft: MatSidenav;

    constructor(
        public http: HttpClient,
        private zone: NgZone,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService
    ) {
        window['angularSaveNumericPackageComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content),
        };
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.saveNumericPackage);

        this.loading = false;
    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any) {
        this.numericPackage.base64 = b64Content.replace(/^data:.*?;base64,/, '');
    }

    uploadNumericPackage(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();

            this.numericPackage.name = fileInput.target.files[0].name;
            this.numericPackage.size = fileInput.target.files[0].size;
            this.numericPackage.type = fileInput.target.files[0].type;
            this.numericPackage.extension = fileInput.target.files[0].name.split('.').pop();
            if (this.numericPackage.label == '') {
                this.numericPackage.label = this.numericPackage.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = function (value: any) {
                window['angularSaveNumericPackageComponent'].componentAfterUpload(value.target.result);
            };

        }
    }

    submitNumericPackage() {
        if (this.numericPackage.size != 0) {
            this.http.post('../../rest/saveNumericPackage', this.numericPackage)
                .subscribe((data: any) => {
                    if (data.errors) {
                        this.notify.error(data.errors);
                    } else {
                        this.numericPackage = {
                            base64: '',
                            name: '',
                            type: '',
                            size: 0,
                            label: '',
                            extension: '',
                        };
                        $('#numericPackageFilePath').val(null);
                        this.notify.success(this.lang.numericPackageImported);

                        if (data.basketRedirection != null) {
                            window.location.href = data.basketRedirection;
                            // action_send_first_request('index.php?display=true&page=manage_action&module=core', 'page',  22, '', 'res_letterbox', 'basket', 'letterbox_coll');
                        }
                    }
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.numericPackage.name = '';
            this.numericPackage.size = 0;
            this.numericPackage.type = '';
            this.numericPackage.base64 = '';
            this.numericPackage.extension = '';

            this.notify.error(this.lang.noNumericPackageSelected);
        }
    }

}
