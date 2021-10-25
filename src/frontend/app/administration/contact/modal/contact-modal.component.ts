import { Component, Inject, ViewChild, Renderer2, OnInit, ElementRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialog } from '@angular/material/dialog';
import { LANG } from '../../../translate.component';
import { HttpClient } from '@angular/common/http';
import { PrivilegeService } from '../../../../service/privileges.service';
import { HeaderService } from '../../../../service/header.service';
import { MatSidenav } from '@angular/material';
import { ConfirmComponent } from '../../../../plugins/modal/confirm.component';
import { catchError, exhaustMap, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '../../../notification.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: 'contact-modal.component.html',
    styleUrls: ['contact-modal.component.scss'],
})
export class ContactModalComponent implements OnInit{
    lang: any = LANG;
    creationMode: boolean = true;
    canUpdate: boolean = false;
    contact: any = null;
    mode: 'update' | 'read' = 'read';
    loadedDocument: boolean = false;

    @ViewChild('drawer', { static: true }) drawer: MatSidenav;
    @ViewChild('contactForm', {static: true}) contactForm: ElementRef;

    constructor(
        public http: HttpClient,
        private privilegeService: PrivilegeService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ContactModalComponent>,
        public headerService: HeaderService,
        public dialog: MatDialog,
        public notify: NotificationService,
        private renderer: Renderer2) {
    }

    ngOnInit(): void {
        if (this.data.contactId !== null) {
            this.contact = {
                id: this.data.contactId,
                type: this.data.contactType
            };
            this.creationMode = false;
        } else {
            this.creationMode = true;
            this.mode = 'update';
            if (this.mode === 'update') {
                $j('.maarch-modal').css({ 'height': '99vh' });
                $j('.maarch-modal').css({ 'width': '99vw' });
            }
            if (this.headerService.getLastLoadedFile() !== null) {
                this.drawer.toggle();
                setTimeout(() => {
                    this.loadedDocument = true;
                }, 200);
            }
        }
        this.canUpdate = this.privilegeService.hasCurrentUserPrivilege('update_contacts');
    }

    switchMode() {
        this.mode = this.mode === 'read' ? 'update' : 'read';
        if (this.mode === 'update') {
            $j('.maarch-modal').css({ 'height': '99vh' });
            $j('.maarch-modal').css({ 'width': '99vw' });
        }

        if (this.headerService.getLastLoadedFile() !== null) {
            this.drawer.toggle();
            setTimeout(() => {
                this.loadedDocument = true;
            }, 200);
        }
    }

    linkContact(contactId: any) {
        const dialogRef = this.dialog.open(ConfirmComponent,
            { panelClass: 'maarch-modal',
                autoFocus: false, disableClose: true,
                data: {
                    title: this.lang.linkContact,
                    msg: this.lang.goToContact
                }
            });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(async () => this.dialogRef.close(contactId)),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
