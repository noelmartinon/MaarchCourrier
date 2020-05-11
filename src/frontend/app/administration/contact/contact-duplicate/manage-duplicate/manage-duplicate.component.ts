import { Component, OnInit, Inject, ViewChildren, QueryList } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HeaderService } from '../../../../../service/header.service';
import { HttpClient } from '@angular/common/http';
import { FunctionsService } from '../../../../../service/functions.service';
import { ContactDetailComponent } from '../../../../contact/contact-detail/contact-detail.component';
import { LANG } from '../../../../translate.component';
import { tap, catchError } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';
import { NotificationService } from '../../../../notification.service';

@Component({
    selector: 'app-manage-duplicate',
    templateUrl: './manage-duplicate.component.html',
    styleUrls: ['./manage-duplicate.component.scss']
})
export class ManageDuplicateComponent implements OnInit {

    lang: any = LANG;

    contactSelected: number = null;

    @ViewChildren('appContactDetail') appContactDetail: QueryList<ContactDetailComponent>;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ManageDuplicateComponent>,
        public headerService: HeaderService,
        private functionsService: FunctionsService) {
    }

    ngOnInit(): void { }

    mergeContact(contact: any, index: number) {

        this.contactSelected = index;

        this.data.duplicate.forEach((contactItem: any, indexContact: number) => {
            Object.keys(this.appContactDetail.toArray()[indexContact].getContactInfo()).forEach(element => {
                if (this.functionsService.empty(this.appContactDetail.toArray()[index].getContactInfo()[element]) && this.appContactDetail.toArray()[index].getContactInfo()[element] !== this.appContactDetail.toArray()[indexContact].getContactInfo()[element]) {
                    this.appContactDetail.toArray()[index].setContactInfo(element, this.appContactDetail.toArray()[indexContact].getContactInfo()[element]);
                }
            });
        });
    }

    resetContact(contact: any, index: number) {

        this.contactSelected = null;

        this.appContactDetail.toArray()[index].resetContact();

    }

    onSubmit() {
        const masterContact: number = this.data.duplicate.filter((contact: any, index: number) => index === this.contactSelected).map((contact: any) => contact.id)[0];
        const slaveContacts: number[] = this.data.duplicate.filter((contact: any, index: number) => index !== this.contactSelected).map((contact: any) => contact.id);

        console.log(masterContact);
        console.log(slaveContacts);

        this.http.post('../rest/contacts/merged', { master : masterContact, saves : slaveContacts}).pipe(
            tap(() => {
                this.dialogRef.close('success');
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
