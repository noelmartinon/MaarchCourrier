import {AfterViewInit, Component, EventEmitter, HostListener, Input, OnDestroy, OnInit, Output, SecurityContext, ViewChild} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {catchError, filter, tap} from 'rxjs/operators';
import {LANG} from '../../app/translate.component';
import {ConfirmComponent} from '../modal/confirm.component';
import {MatDialog, MatDialogRef} from '@angular/material/dialog';
import {HeaderService} from '../../service/header.service';
import {Subject} from 'rxjs/internal/Subject';
import {of} from 'rxjs/internal/observable/of';
import {DomSanitizer} from '@angular/platform-browser';
import { NotificationService } from '../../service/notification/notification.service';

declare var $: any;

@Component({
    selector: 'app-collabora-online-viewer',
    templateUrl: 'collabora-online-viewer.component.html',
    styleUrls: ['collabora-online-viewer.component.scss'],
})
export class CollaboraOnlineViewerComponent implements OnInit, AfterViewInit, OnDestroy {

    lang: any = LANG;

    loading: boolean = true;

    @Input() editMode: boolean = false;
    @Input() file: any = {};
    @Input() params: any = {};
    @Input() hideCloseEditor: any = false;

    @Output() triggerAfterUpdatedDoc = new EventEmitter<string>();
    @Output() triggerCloseEditor = new EventEmitter<string>();
    @Output() triggerModifiedDocument = new EventEmitter<string>();

    editorConfig: any;
    key: string = '';
    isSaving: boolean = false;
    fullscreenMode: boolean = false;

    allowedExtension: string[] = [
        'doc',
        'docx',
        'dotx',
        'odt',
        'ott',
        'rtf',
        'txt',
        'html',
        'xlsl',
        'xlsx',
        'xltx',
        'ods',
        'ots',
        'csv',
    ];

    private eventAction = new Subject<any>();
    dialogRef: MatDialogRef<any>;

    editorUrl: any = '';
    token: any = '';

    @ViewChild('collaboraFrame', { static: false }) collaboraFrame: any;

    @HostListener('window:message', ['$event'])
    onMessage(e: any) {
        console.log(e);
        const response = JSON.parse(e.data);
        // EVENT TO CONSTANTLY UPDATE CURRENT DOCUMENT
        console.log(response);
        if (response.MessageId === 'Action_Save_Resp') {
            console.log('got message : action save response -> doc is saving');
        } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === false && this.isSaving) {
            console.log('got message = doc is not modified -> finished saving');
            this.triggerAfterUpdatedDoc.emit();
            this.getTmpFile();
        } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === true) {
            console.log('got message = doc is modified');
            this.triggerModifiedDocument.emit();
        } else if (response.MessageId === 'App_LoadingStatus' && response.Values.Status === 'Document_Loaded') {
            console.log('got message = doc is loaded');
            const message = {'MessageId': 'Host_PostmessageReady'};
            console.log('getConfiguration, sending message : ' + JSON.stringify(message));
            this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');
        }
    }

    constructor(
        public http: HttpClient,
        public dialog: MatDialog,
        private notify: NotificationService,
        private sanitizer: DomSanitizer,
        public headerService: HeaderService) { }

    quit() {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.close, msg: this.lang.confirmCloseEditor } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                this.closeEditor();
            })
        ).subscribe();
    }

    closeEditor() {
        if (this.headerService.sideNavLeft !== null) {
            this.headerService.sideNavLeft.open();
        }
        $('iframe[name=\'frameEditor\']').css('position', 'initial');
        this.fullscreenMode = false;

        // const message = {
        //     'MessageId': 'Action_Close',
        //     'Values': null
        // };
        // this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');

        this.triggerAfterUpdatedDoc.emit();
        this.triggerCloseEditor.emit();
    }

    saveDocument() {
        this.isSaving = true;

        const message = {
            'MessageId': 'Action_Save',
            'Values': {
                'Notify': true,
                'ExtendedData': 'FinalSave=True',
                'DontTerminateEdit': true,
                'DontSaveIfUnmodified': true
            }
        };
        console.log('getDocument, sending message : ' + JSON.stringify(message));
        this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');
    }

    async ngOnInit() {
        this.key = this.generateUniqueId();

        if (this.canLaunchCollaboraOnline()) {
            await this.checkServerStatus();

            await this.getConfiguration();

            this.loading = false;
        }
    }

    canLaunchCollaboraOnline() {
        if (this.isAllowedEditExtension(this.file.format)) {
            return true;
        } else {
            this.notify.error(this.lang.onlyofficeEditDenied + ' <b>' + this.file.format + '</b> ' + this.lang.onlyofficeEditDenied2);
            this.triggerCloseEditor.emit();
            return false;
        }
    }

    checkServerStatus() {
        return new Promise((resolve) => {
            // const regex = /127\.0\.0\.1/g;
            // const regex2 = /localhost/g;
            // if (this.appUrl.match(regex) !== null || this.appUrl.match(regex2) !== null) {
            //     this.notify.error(`${this.lang.errorOnlyoffice1}`);
            //     this.triggerCloseEditor.emit();
            // } else {
                this.http.get(`../rest/collaboraOnline/available`).pipe(
                    tap((data: any) => {
                        if (data.isAvailable) {
                            resolve(true);
                        } else {
                            // this.notify.error(`${this.lang.errorOnlyoffice2} ${this.onlyOfficeUrl}`);
                            this.triggerCloseEditor.emit();
                        }
                    }),
                    catchError((err) => {
                        this.notify.error(`${this.lang[err.error.lang]}`);
                        this.triggerCloseEditor.emit();
                        return of(false);
                    }),
                ).subscribe();
            // }
        });
    }

    getTmpFile() {
        return new Promise((resolve) => {
            this.http.post('../rest/collaboraOnline/file', {token: this.token, data: this.params.dataToMerge}).pipe(
                tap((data: any) => {
                    this.file = {
                        name: this.key,
                        format: data.format,
                        type: null,
                        contentMode: 'base64',
                        content: data.content,
                        src: null
                    };
                    this.eventAction.next(this.file);
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    return of(false);
                }),
            ).subscribe();
        });
    }

    generateUniqueId(length: number = 5) {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    ngAfterViewInit() {

    }

    getConfiguration() {
        return new Promise((resolve) => {
            this.http.post('../rest/collaboraOnline/configuration', { resId: this.params.objectId, type: this.params.objectType, mode: this.params.objectMode}).pipe(
                tap((data: any) => {
                    this.editorUrl = data.url;
                    this.editorUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.editorUrl);
                    this.token = data.token;
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    return of(false);
                }),
            ).subscribe();
        });
    }

    getFile() {
        // return this.file;
        this.saveDocument();
        return this.eventAction.asObservable();
    }

    ngOnDestroy() {
        this.eventAction.complete();
    }

    openFullscreen() {
        $('iframe[name=\'frameEditor\']').css('top', '0px');
        $('iframe[name=\'frameEditor\']').css('left', '0px');

        if (!this.fullscreenMode) {
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.close();
            }
            $('iframe[name=\'frameEditor\']').css('position', 'fixed');
            $('iframe[name=\'frameEditor\']').css('z-index', '2');
        } else {
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.open();
            }
            $('iframe[name=\'frameEditor\']').css('position', 'initial');
            $('iframe[name=\'frameEditor\']').css('z-index', '1');
        }
        this.fullscreenMode = !this.fullscreenMode;
    }

    isAllowedEditExtension(extension: string) {
        return this.allowedExtension.filter(ext => ext.toLowerCase() === extension.toLowerCase()).length > 0;
    }
}
