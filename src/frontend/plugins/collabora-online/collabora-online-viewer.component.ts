import {AfterViewInit, Component, EventEmitter, HostListener, Input, OnDestroy, OnInit, Output, ViewChild} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {catchError, filter, tap} from 'rxjs/operators';
import {LANG} from '../../app/translate.component';
import {ConfirmComponent} from '../modal/confirm.component';
import {MatDialog, MatDialogRef} from '@angular/material/dialog';
import {HeaderService} from '../../service/header.service';
import {Subject} from 'rxjs/internal/Subject';
import {of} from 'rxjs/internal/observable/of';
import {DomSanitizer} from '@angular/platform-browser';
import { NotificationService } from '../../app/notification.service';

declare function $j(selector: any): any;

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
    key: number = 0;
    isSaving: boolean = false;
    isModified: boolean = false;
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
        // console.log(e);
        const response = JSON.parse(e.data);
        // EVENT TO CONSTANTLY UPDATE CURRENT DOCUMENT
        if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === false) {
            this.isModified = false;
        }
        if (response.MessageId === 'Action_Save_Resp' && response.Values.success === true && !this.isModified) {
            this.triggerAfterUpdatedDoc.emit();
            this.getTmpFile();
        } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === false && this.isSaving) {
            // Collabora sends 'Action_Save_Resp' when it starts saving the document, then sends Doc_ModifiedStatus with Modified = false when it is done saving
            this.triggerAfterUpdatedDoc.emit();
            this.getTmpFile();
        } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === true) {
            this.isModified = true;
            this.triggerModifiedDocument.emit();
        } else if (response.MessageId === 'App_LoadingStatus' && response.Values.Status === 'Document_Loaded') {
            const message = {'MessageId': 'Host_PostmessageReady'};
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
        $j('iframe[name=\'frameEditor\']').css('position', 'initial');
        this.fullscreenMode = false;

        let message: { Values: null; MessageId: string };
        message = {
            'MessageId': 'Action_Close',
            'Values':    null
        };
        this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');

        this.deleteTmpFile();

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
                'DontSaveIfUnmodified': false
            }
        };
        this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');
    }

    async ngOnInit() {
        this.key = this.generateUniqueId(10);

        if (this.canLaunchCollaboraOnline()) {
            await this.checkServerStatus();

            if (this.params.objectType === 'templateModification' || this.params.objectType === 'templateCreation') {
                this.params.objectMode = this.params.objectType === 'templateModification' ? 'edition' : 'creation';
                this.params.objectType = 'template';
            }

            this.params.objectPath = undefined;
            if (typeof this.params.objectId === 'string' && this.params.objectType === 'template') {
                this.params.objectPath = this.params.objectId;
                this.params.objectId = this.key;
            } else if (typeof this.params.objectId === 'string' && this.params.objectType === 'encodedResource') {
                this.params.content = this.params.objectId;
                this.params.objectId = this.key;
                this.params.objectMode = 'encoded';
                this.params.objectType = 'template';

                await this.saveEncodedFile();
            }

            await this.getConfiguration();

            this.loading = false;
        }
    }

    canLaunchCollaboraOnline() {
        if (this.isAllowedEditExtension(this.file.format)) {
            return true;
        } else {
            this.notify.error(this.lang.onlyofficeEditDenied + ' <b>' + this.file.format + '</b> ' + this.lang.collaboraOnlineEditDenied2);
            this.triggerCloseEditor.emit();
            return false;
        }
    }

    checkServerStatus() {
        return new Promise((resolve) => {
            if (location.host === '127.0.0.1' || location.host === 'localhost') {
                this.notify.error(`${this.lang.errorCollaboraOnline1}`);
                this.triggerCloseEditor.emit();
            } else {
                this.http.get(`../../rest/collaboraOnline/available`).pipe(
                    tap((data: any) => {
                        if (data.isAvailable) {
                            resolve(true);
                        } else {
                            this.notify.error(`${this.lang.errorCollaboraOnline2}`);
                            this.triggerCloseEditor.emit();
                        }
                    }),
                    catchError((err) => {
                        this.notify.error(`${this.lang[err.error.lang]}`);
                        this.triggerCloseEditor.emit();
                        return of(false);
                    }),
                ).subscribe();
            }
        });
    }

    getTmpFile() {
        return new Promise((resolve) => {
            this.http.post('../../rest/collaboraOnline/file', {token: this.token, data: this.params.dataToMerge}).pipe(
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

    deleteTmpFile() {
        return new Promise((resolve) => {
            this.http.delete('../../rest/collaboraOnline/file?token=' + this.token).pipe(
                tap(() => {
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

    saveEncodedFile() {
        return new Promise((resolve) => {
            this.http.post('../../rest/collaboraOnline/encodedFile', {content: this.params.content, format: this.file.format, key: this.key}).pipe(
                tap(() => {
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
        const characters = '0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return parseInt(result, 10);
    }

    ngAfterViewInit() {

    }

    getConfiguration() {
        return new Promise((resolve) => {
            this.http.post('../../rest/collaboraOnline/configuration', {
                resId: this.params.objectId,
                type: this.params.objectType,
                mode: this.params.objectMode,
                format: this.file.format,
                path: this.params.objectPath
            }).pipe(
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
        this.saveDocument();
        return this.eventAction.asObservable();
    }

    ngOnDestroy() {
        this.eventAction.complete();
    }

    openFullscreen() {
        $j('iframe[name=\'frameEditor\']').css('top', '0px');
        $j('iframe[name=\'frameEditor\']').css('left', '0px');

        if (!this.fullscreenMode) {
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.close();
            }
            $j('iframe[name=\'frameEditor\']').css('position', 'fixed');
            $j('iframe[name=\'frameEditor\']').css('z-index', '2');
        } else {
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.open();
            }
            $j('iframe[name=\'frameEditor\']').css('position', 'initial');
            $j('iframe[name=\'frameEditor\']').css('z-index', '1');
        }
        this.fullscreenMode = !this.fullscreenMode;
    }

    isAllowedEditExtension(extension: string) {
        return this.allowedExtension.filter(ext => ext.toLowerCase() === extension.toLowerCase()).length > 0;
    }
}
