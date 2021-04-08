import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { catchError, exhaustMap, filter, map, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { MatDialog } from '@angular/material/dialog';

declare let tinymce: any;

@Component({
    selector: 'app-mail-signatures-administration',
    templateUrl: 'mail-signatures-administration.component.html',
    styleUrls: ['mail-signatures-administration.component.scss']
})
export class MailSignaturesAdministrationComponent implements OnInit, OnDestroy {

    @Input() mode: 'user' | 'public' = 'user';

    loading: boolean = false;
    addMode: boolean = false;

    route: string = '';

    newSignature: any = {
        label: '',
        content: ''
    };

    signatures: any[] = [];
    signaturesClone: any[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
    ) { }

    ngOnInit(): void {
        this.route = this.mode === 'user' ? '../rest/currentUser/emailSignature' : '../rest/administration/emailSignature';

        this.getSignatures();
        // this.initMce();
    }

    ngOnDestroy(): void {
        tinymce.remove();
    }

    getSignatures() {
        return new Promise((resolve) => {
            this.http.get(this.route).pipe(
                map((data: any) => {
                    data = data.emailSignatures.map((sign: any) => ({
                        id: sign.id,
                        label: sign.title,
                        content: sign.html_body
                    }));
                    return data;
                }),
                tap((data: any) => {
                    this.signatures = data;
                    this.signaturesClone = JSON.parse(JSON.stringify(this.signatures));
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    initMce(id: string = '') {
        tinymce.remove('textarea#emailSignature' + id);
        // LOAD EDITOR TINYMCE for MAIL SIGN
        tinymce.baseURL = '../node_modules/tinymce';
        tinymce.suffix = '.min';
        tinymce.init({
            selector: 'textarea#emailSignature' + id,
            statusbar: false,
            language: this.translate.instant('lang.langISO').replace('-', '_'),
            language_url: `../node_modules/tinymce-i18n/langs/${this.translate.instant('lang.langISO').replace('-', '_')}.js`,
            external_plugins: {
                'maarch_b64image': '../../src/frontend/plugins/tinymce/maarch_b64image/plugin.min.js'
            },
            menubar: false,
            toolbar: 'undo | bold italic underline | alignleft aligncenter alignright | maarch_b64image | forecolor',
            theme_buttons1_add: 'fontselect,fontsizeselect',
            theme_buttons2_add_before: 'cut,copy,paste,pastetext,pasteword,separator,search,replace,separator',
            theme_buttons2_add: 'separator,insertdate,inserttime,preview,separator,forecolor,backcolor',
            theme_buttons3_add_before: 'tablecontrols,separator',
            theme_buttons3_add: 'separator,print,separator,ltr,rtl,separator,fullscreen,separator,insertlayer,moveforward,movebackward,absolut',
            theme_toolbar_align: 'left',
            theme_advanced_toolbar_location: 'top',
            theme_styles: 'Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1'

        });
    }

    switchMode() {
        this.addMode = !this.addMode;
        if (!this.addMode) {
            tinymce.remove('textarea#emailSignature');
        } else {
            setTimeout(() => {
                this.initMce();
            }, 0);
        }
    }

    editSignature(index: number) {
        this.signatures[index].editMode = true;
        setTimeout(() => {
            this.initMce(this.signatures[index].id);
        }, 0);
    }

    closeEditSignature(index: number) {
        tinymce.remove('textarea#emailSignature' + this.signatures[index].id);
        this.signatures[index].editMode = false;
        this.signatures[index] = JSON.parse(JSON.stringify(this.signaturesClone[index]));
    }

    createSignature() {
        this.newSignature.content = tinymce.get('emailSignature').getContent();

        // FOR TEST
        this.newSignature.id = 12;
        this.signatures.push(this.newSignature);
        this.signaturesClone = JSON.parse(JSON.stringify(this.signatures));
        this.addMode = false;
        this.newSignature = {
            label: '',
            content: ''
        };

        /* this.http.post(this.route, this.formatSignature(this.newSignature)).pipe(
            tap((data: any) => {
                this.newSignature.id = data.id
                this.signatures.push(this.newSignature);
                this.addMode = false;
                this.signaturesClone = JSON.parse(JSON.stringify(this.signatures));
                this.newSignature = {
                    label: '',
                    content: ''
                };
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();*/
    }

    saveSignature(index: number) {
        this.signatures[index].content = tinymce.get('emailSignature' + this.signatures[index].id).getContent();

        // FOR TEST
        this.signatures[index].editMode = false;
        this.signaturesClone[index] = JSON.parse(JSON.stringify(this.signatures[index]));

        /* this.http.put(this.route + this.signatures[index].id, this.formatSignature(signature)).pipe(
            tap((data: any) => {
                this.newSignature.id = data.id
                this.signatures.push(this.newSignature);
                this.addMode = false;
                this.newSignature = {
                    label: '',
                    content: ''
                };
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();*/
    }

    deleteSignature(index: number) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: `${this.translate.instant('lang.delete')} "${this.signatures[index].label}"`, msg: this.translate.instant('lang.confirmAction') } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            // exhaustMap(() => this.http.delete(`../rest/???/${this.signatures[index].id}`)),
            tap(() => {
                this.signatures.splice(index, 1);
                this.signaturesClone.splice(index, 1);
                this.notify.success(this.translate.instant('lang.signatureDeleted'));
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatSignature(signature: any) {
        return {
            label: signature.label,
            content: signature.content
        };
    }
}
