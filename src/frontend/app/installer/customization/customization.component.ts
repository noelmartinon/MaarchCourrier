import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ValidatorFn } from '@angular/forms';
import { LANG } from '../../translate.component';
import { StepAction } from '../types';
import { DomSanitizer } from '@angular/platform-browser';
import { NotificationService } from '../../../service/notification/notification.service';

declare var tinymce: any;

@Component({
    selector: 'app-customization',
    templateUrl: './customization.component.html',
    styleUrls: ['./customization.component.scss']
})
export class CustomizationComponent implements OnInit {
    lang: any = LANG;
    stepFormGroup: FormGroup;

    customId: string = 'cs_maarchcourrier';
    appName: string = 'Maarch Courrier 20.10';
    loginMsg: string = '<span style="color:#24b0ed"><strong>Découvrez votre application via</strong></span>&nbsp;<a title="le guide de visite" href="https://docs.maarch.org/gitbook/html/MaarchCourrier/19.04/guu/home.html" target="_blank"><span style="color:#f99830;"><strong>le guide de visite en ligne</strong></span></a>';
    homeMsg: string = '<p>D&eacute;couvrez <strong>Maarch Courrier 20.10</strong> avec <a title="notre guide de visite" href="https://docs.maarch.org/" target="_blank"><span style="color:#f99830;"><strong>notre guide de visite en ligne</strong></span></a>.</p>';
    selectedBackground: string = 'bodylogin.jpg';
    uploadedImg: string = '';
    uploadedLogo: string = '../rest/images?image=logo';

    backgroundList: any[] = [];
    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
        private sanitizer: DomSanitizer
    ) {
        const valIdentifier: ValidatorFn[] = [Validators.pattern(/^[a-zA-Z0-9_\-]*$/), Validators.required];

        this.stepFormGroup = this._formBuilder.group({
            firstCtrl: ['success', Validators.required],
            customId: ['cs_maarchcourrier', valIdentifier],
            appName: ['Maarch Courrier 20.10', Validators.required],
            loginMsg: ['<span style="color:#24b0ed"><strong>Découvrez votre application via</strong></span>&nbsp;<a title="le guide de visite" href="https://docs.maarch.org/gitbook/html/MaarchCourrier/19.04/guu/home.html" target="_blank"><span style="color:#f99830;"><strong>le guide de visite en ligne</strong></span></a>'],
            homeMsg: ['<p>D&eacute;couvrez <strong>Maarch Courrier 20.10</strong> avec <a title="notre guide de visite" href="https://docs.maarch.org/" target="_blank"><span style="color:#f99830;"><strong>notre guide de visite en ligne</strong></span></a>.</p>'],
            selectedBackground: ['bodylogin.jpg'],
        });
        this.backgroundList = Array.from({ length: 16 }).map((_, i) => {
            return {
                filename: `${i + 1}.jpg`,
                url: `assets/${i + 1}.jpg`,
            };
          });
    }

    ngOnInit(): void {
        setTimeout(() => {
            this.initMce();
        }, 200);
    }


    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
    }


    getFormGroup() {
        return this.stepFormGroup;
    }

    initMce() {
        tinymce.init({
            selector: 'textarea',
            base_url: '../node_modules/tinymce/',
            /*setup: (editor: any) => {
                editor.on('init', (e: any) => {
                    this.loading = false;
                });
            },*/
            height: '150',
            suffix: '.min',
            language: LANG.langISO.replace('-', '_'),
            language_url: `../node_modules/tinymce-i18n/langs/${LANG.langISO.replace('-', '_')}.js`,
            menubar: false,
            statusbar: false,
            plugins: [
                'autolink'
            ],
            external_plugins: {
                'maarch_b64image': '../../src/frontend/plugins/tinymce/maarch_b64image/plugin.min.js'
            },
            toolbar_sticky: true,
            toolbar_drawer: 'floating',
            toolbar: 'undo redo | fontselect fontsizeselect | bold italic underline strikethrough forecolor | maarch_b64image | \
        alignleft aligncenter alignright alignjustify \
        bullist numlist outdent indent | removeformat'
        });
    }

    getInfoToInstall(): StepAction[] {
        return [{
            body : {
                customId: this.customId,
            },
            description : 'Initialisation de l\'instance',
            route : '../rest/installer/custom',
            installPriority : 1
        }];
    }

    uploadTrigger(fileInput: any, mode: string) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            const reader = new FileReader();

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = (value: any) => {
                if (mode === 'logo') {
                    this.uploadedLogo = value.target.result;
                } else {
                    this.backgroundList.push({
                        filename: value.target.result,
                        url: value.target.result,
                    });
                    this.selectedBackground = value.target.result;
                }
            };
        }
    }

    logoURL() {
        return this.sanitizer.bypassSecurityTrustUrl(this.uploadedLogo);
      }
}
