import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../notification.service';
import { LANG } from '../../translate.component';

declare var tinymce: any;

@Component({
    selector: 'app-customization',
    templateUrl: './customization.component.html',
    styleUrls: ['./customization.component.scss']
})
export class CustomizationComponent implements OnInit {

    stepFormGroup: FormGroup;

    docserversPath: string = '/opt/maaarch/docservers/';

    appName: string = 'Maarch Courrier 20.10';
    loginMsg: string = '<span style="color:#24b0ed"><strong>Découvrez votre application via</strong></span>&nbsp;<a title="le guide de visite" href="https://docs.maarch.org/gitbook/html/MaarchCourrier/19.04/guu/home.html" target="_blank"><span style="color:#f99830;"><strong>le guide de visite en ligne</strong></span></a>';
    homeMsg: string = '<p>D&eacute;couvrez <strong>Maarch Courrier 20.10</strong> avec <a title="notre guide de visite" href="https://docs.maarch.org/" target="_blank"><span style="color:#f99830;"><strong>notre guide de visite en ligne</strong></span></a>.</p>';
    selectedBackground: string = 'bodylogin.jpg';

    backgroundList: any[] = [];

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
    ) {
        this.stepFormGroup = this._formBuilder.group({
            firstCtrl: ['success', Validators.required],
        });
        this.backgroundList = Array.from({ length: 16 }).map((_, i) => {
            return {
              filename: `${i + 1}.jpg`,
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

}
