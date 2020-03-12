import { COMMA } from '@angular/cdk/keycodes';
import { Component, OnInit, Inject, ElementRef, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { Observable, of } from 'rxjs';
import { MatDialog, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { switchMap, catchError, filter, exhaustMap, tap, debounceTime, distinctUntilChanged, finalize, map } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../../service/functions.service';
import { ContactService } from '../../../service/contact.service';
import { AppService } from '../../../service/app.service';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { PrivilegeService } from '../../../service/privileges.service';
import { HeaderService } from '../../../service/header.service';
import { StripTagsPipe } from 'ngx-pipes';

declare var angularGlobals: any;
declare function $j(selector: any): any;

@Component({
    selector: 'app-sent-numeric-package-page',
    templateUrl: './sent-numeric-package-page.component.html',
    styleUrls: ['./sent-numeric-package-page.component.scss'],
    providers: [ContactService, AppService, StripTagsPipe],
})
export class SentNumericPackagePageComponent implements OnInit {


    lang: any = LANG;
    loading: boolean = true;

    readonly separatorKeysCodes: number[] = [COMMA];

    availableEmailModels: any[] = [];
    availableSignEmailModels: any[] = [];

    resourceData: any = null;
    availableSenders: any[] = [];
    currentSender: any = {};

    recipients: any[] = [];

    copies: any[] = [];

    invisibleCopies: any[] = [];

    recipientsCtrl: FormControl = new FormControl();

    emailSignListForm = new FormControl();
    templateEmailListForm = new FormControl();

    filteredEmails: Observable<string[]>;

    showCopies: boolean = false;
    showInvisibleCopies: boolean = false;

    emailCreatorId: number = null;
    emailId: number = null;
    emailStatus: string = 'WAITING';
    emailContent: string = '';
    currentEmailAttachTool: string = '';
    emailAttachTool: any = {
        document: {
            icon: 'fa fa-file',
            title: this.lang.attachMainDocument,
            list: []
        },
        notes: {
            icon: 'fas fa-pen-square',
            title: this.lang.attachNote,
            list: []
        },
        attachments: {
            icon: 'fa fa-paperclip',
            title: this.lang.attachAttachment,
            list: []
        },
    };
    emailAttach: any = [];

    numericPackage: any = {
        mainExchangeDoc: null,
        "object": '',
        "contacts": [],
        "joinFile": [],
        "joinAttachment": [],
        "notes": [],
        "content": "",
        "senderEmail": null
    }

    communicationType: string = null;
    reference: string = null;

    maarch2maarchUrl: string = `https://docs.maarch.org/gitbook/html/MaarchCourrier/${angularGlobals.applicationVersion.split('.')[0] + '.' + angularGlobals.applicationVersion.split('.')[1]}/guat/guat_exploitation/maarch2maarch.html`;

    @ViewChild('recipientsInput', { static: true }) recipientsInput: ElementRef<HTMLInputElement>;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialog: MatDialog,
        public dialogRef: MatDialogRef<SentNumericPackagePageComponent>,
        public functions: FunctionsService,
        private contactService: ContactService,
        public privilegeService: PrivilegeService,
        public headerService: HeaderService,
        private stringPipe: StripTagsPipe,
    ) { }

    async ngOnInit(): Promise<void> {

        await this.getAttachElements();

        if (this.data.emailId) {
            await this.getNumericPackageData(this.data.emailId);
        }

        if (this.canManageMail()) {
            this.initEmailModelsList();
            this.initM2MList();
            this.initSignEmailModelsList();

            await this.getResourceData();
            await this.getM2MSenders();

        }
        this.loading = false;
    }

    isBadEmailFormat(email: string) {
        const regex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/g;

        return email.trim().match(regex) === null;
    }

    closeModal(state: string = '') {
        this.dialogRef.close(state);
    }

    addRecipient(item: any) {
        this.recipients.push(item);
        this.recipientsInput.nativeElement.value = '';
        this.recipientsCtrl.setValue('');
    }

    mergeEmailTemplate(templateId: any) {

        this.templateEmailListForm.reset();

        this.http.post(`../../rest/templates/${templateId}/mergeEmail`, { data: { resId: this.data.resId } }).pipe(
            tap((data: any) => {
                var textArea = document.createElement('textarea');
                textArea.innerHTML = data.mergedDocument;
                this.emailContent += this.stringPipe.transform(textArea.value);
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    mergeSignEmailTemplate(templateId: any) {

        this.emailSignListForm.reset();

        this.http.get(`../../rest/currentUser/emailSignatures/${templateId}`).pipe(
            tap((data: any) => {
                var textArea = document.createElement('textarea');
                textArea.innerHTML = data.mergedDocument;
                this.emailContent += this.stringPipe.transform(textArea.value);
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    remove(item: any, type: string): void {
        if (this.canManageMail()) {
            const index = this[type].indexOf(item);

            if (index >= 0) {
                this[type].splice(index, 1);
            }
        }
    }

    getNumericPackageData(emailId: number) {
        return new Promise((resolve) => {
            this.http.get(`../../rest/messageExchanges/${emailId}`).pipe(
                map((data: any) => data.messageExchange),
                tap((data: any) => {
                    this.emailCreatorId = data.userId;

                    this.recipients = [{
                        label: data.recipent,
                        m2m: data.recipent
                    }];

                    this.currentSender.label = data.sender;
                    this.numericPackage.object = data.object;
                    this.emailStatus = data.status.toUpperCase();
                    this.numericPackage.content = data.body;
                    this.communicationType = data.communicationType;
                    this.reference = data.reference;

                    Object.keys(data.document).forEach(element => {
                        if (['id', 'isLinked', 'original'].indexOf(element) === -1) {
                            data.document[element].forEach((dataAttach: any) => {
                                const elem = this.emailAttachTool[element].list.filter((item: any) => item.id === dataAttach.id || item.id === dataAttach);
                                if (elem.length > 0) {
                                    this.emailAttach[element] = elem.map((item: any) => {
                                        return {
                                            ...item,
                                            format: dataAttach.original || dataAttach.original === undefined ? item.format : 'pdf',
                                            original: dataAttach.original,
                                            size: dataAttach.original || dataAttach.original === undefined ? item.size : item.convertedDocument.size
                                        }
                                    })
                                }
                            });
                        } else if (element === 'isLinked' && data.document.isLinked === true) {
                            this.emailAttach.document.isLinked = true;
                            this.emailAttach.document.original = data.document.original;
                            this.emailAttach.document.size = this.emailAttach.document.original ? this.emailAttachTool.document.list[0].size : this.emailAttachTool.document.list[0].convertedDocument.size
                        }
                    });

                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getResourceData() {
        return new Promise((resolve) => {
            this.http.get(`../../rest/resources/${this.data.resId}?light=true`).pipe(
                tap((data: any) => {
                    this.resourceData = data;
                    this.numericPackage.object = this.resourceData.subject;

                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    setSender(id: number) {
        this.http.get(`../../rest/contacts/${id}`).pipe(
            tap((data: any) => {
                if (!this.functions.empty(data.email)) {
                    this.recipients.push(
                        {
                            label: this.contactService.formatContact(data),
                            email: data.email
                        }
                    )
                }
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getM2MSenders() {
        return new Promise((resolve) => {
            this.http.get('../../rest/messageExchangesInitialization').pipe(
                tap((data: any) => {
                    this.availableSenders = data.entities;
                    this.currentSender = this.availableSenders[0]
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getAttachElements() {
        return new Promise((resolve) => {
            this.http.get(`../../rest/resources/${this.data.resId}/emailsInitialization`).pipe(
                tap((data: any) => {
                    Object.keys(data).forEach(element => {
                        if (element === 'resource') {
                            this.emailAttachTool.document.list = [];
                            if (!this.functions.empty(data[element])) {
                                this.emailAttachTool.document.list = [data[element]];
                            }
                        } else {
                            this.emailAttachTool[element].list = data[element].map((item: any) => {
                                return {
                                    ...item,
                                    original: item.original !== undefined ? item.original : true,
                                    title: item.chrono !== undefined ? `${item.chrono} - ${item.label} (${item.typeLabel})` : `${item.label} (${item.typeLabel})`
                                }
                            });
                        }
                    });
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    initM2MList() {
        this.recipientsCtrl.valueChanges.pipe(
            filter(value => value !== null),
            debounceTime(300),
            tap((value) => {
                if (value.length === 0) {
                    this.filteredEmails = of([]);
                }
            }),
            filter(value => value.length > 2),
            distinctUntilChanged(),
            switchMap(data => this.http.get('../../rest/autocomplete/contacts/m2m', { params: { "search": data } })),
            tap((data: any) => {
                data = data.map((contact: any) => {
                    return {
                        ...contact,
                        address: this.contactService.formatContact(contact),
                        label: this.contactService.formatContact(contact)
                    }
                });
                this.filteredEmails = of(data);
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }


    initEmailModelsList() {
        this.http.get(`../../rest/resources/${this.data.resId}/emailTemplates`).pipe(
            tap((data: any) => {
                this.availableEmailModels = data.templates;
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    initSignEmailModelsList() {
        this.http.get(`../../rest/currentUser/emailSignatures`).pipe(
            tap((data: any) => {
                this.availableSignEmailModels = data.emailSignatures;
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    resetAutocomplete() {
        this.filteredEmails = of([]);
    }

    onSubmit() {
        this.loading = true;
        this.emailStatus = 'WAITING';
        if (this.data.emailId === null) {
            if (this.numericPackage.object === '') {
                const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.confirm, msg: this.lang.warnEmptySubject } });

                dialogRef.afterClosed().pipe(
                    filter((data: string) => data === 'ok'),
                    tap(() => {
                        this.createEmail(true);
                    })
                ).subscribe();
            } else {
                this.createEmail(true);
            }

        } else {
            this.updateEmail(true);
        }
    }

    createEmail(closeModal: boolean = true) {
        this.http.post(`../../rest/resources/${this.data.resId}/messageExchange`, this.formatNumericPackage()).pipe(
            tap(() => {
                this.notify.success(`Pli numérique envoyé`);

                this.closeModal('success');
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deleteEmail() {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../../rest/emails/${this.data.emailId}`)),
            tap(() => {
                this.notify.success(this.lang.emailDeleted);
                this.closeModal('success');
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateEmail(closeModal: boolean = true) {
        this.http.put(`../../rest/emails/${this.data.emailId}`, this.formatNumericPackage()).pipe(
            tap(() => {
                if (this.emailStatus === 'DRAFT') {
                    // this.notify.success(this.lang.draftUpdated);
                } else {
                    this.notify.success(`Pli numérique envoyé`);
                }

                if (closeModal) {
                    this.closeModal('success');
                }
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    saveDraft() {
        this.closeModal();
        /*if (this.canManageMail()) {
          this.emailStatus = 'DRAFT';
          if (this.data.emailId === null) {
            if (!this.functions.empty(tinymce.get('emailSignature').getContent())) {
              this.createEmail(true);
            } else {
              this.closeModal();
            }
          } else {
            this.updateEmail(true);
          }
        } else {
          this.closeModal();
        }*/
    }

    toggleAttach(item: any, type: string, mode: string) {
        if (this.numericPackage.mainExchangeDoc === null) {
            this.numericPackage.mainExchangeDoc = {
                ...item,
                typeLabel: item.typeLabel !== undefined ? item.typeLabel : this.lang.mainDocument,
                type: type
            }
        } else {
            this.emailAttach.push({
                ...item,
                typeLabel: item.typeLabel !== undefined ? item.typeLabel : this.lang.mainDocument,
                type: type
            });
        }
    }

    removeAttach(index: number) {
        this.emailAttach.splice(index, 1);
    }

    formatNumericPackage() {
        const numericPackage: any = {};
        if (this.numericPackage.mainExchangeDoc !== null) {
            let typeDoc = 'res_letterbox';
            if (this.numericPackage.mainExchangeDoc.type === 'attachments') {
                typeDoc = 'res_attachments';
            } else if (this.numericPackage.mainExchangeDoc.type === 'notes') {
                typeDoc = 'notes';
            }
            numericPackage.joinFile = [parseInt(this.numericPackage.mainExchangeDoc.id)];
            numericPackage.mainExchangeDoc = `${typeDoc}__${this.numericPackage.mainExchangeDoc.id}`
        }
        numericPackage.object = this.numericPackage.object;
        numericPackage.content = this.numericPackage.content;
        numericPackage.contacts = this.recipients.map(recipient => recipient.id);
        numericPackage.joinAttachment = this.emailAttach.filter((attach: any) => attach.type === 'attachments').map((attach: any) => attach.id);
        numericPackage.notes = this.emailAttach.filter((attach: any) => attach.type === 'notes').map((attach: any) => attach.id);
        numericPackage.senderEmail = this.currentSender.id;

        return numericPackage;
    }

    isSelectedAttach(item: any, type: string) {
        return this.emailAttach.filter((attach: any) => attach.id === item.id && attach.type === type).length > 0 || (this.numericPackage.mainExchangeDoc !== null && this.numericPackage.mainExchangeDoc.id === item.id && type === this.numericPackage.mainExchangeDoc.type);
    }

    isSelectedAttachType(type: string) {
        return this.emailAttach.filter((attach: any) => attach.type === type).length > 0 || (this.numericPackage.mainExchangeDoc !== null && type === this.numericPackage.mainExchangeDoc.type);
    }

    canManageMail() {
        if ((this.data.emailId === null) || (this.emailStatus !== 'SENT' && this.headerService.user.id === this.emailCreatorId)) {
            this.recipientsCtrl.enable();
            return true;
        } else {
            this.recipientsCtrl.disable();
            return false;
        }
    }

    compareSenders(sender1: any, sender2: any) {
        return (sender1.label === sender2.label || ((sender1.label === null || sender2.label === null) && (sender1.entityId === null || sender2.entityId === null))) && sender1.entityId === sender2.entityId && sender1.email === sender2.email;
    }
}
