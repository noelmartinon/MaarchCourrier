import { Component, Inject, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { FunctionsService } from '../../../../../service/functions.service';
import { ContactService } from '../../../../../service/contact.service';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';

@Component({
    templateUrl: 'contact-search-modal.component.html',
    styleUrls: ['contact-search-modal.component.scss'],
    providers: [ContactService]
})

export class ContactSearchModal implements OnInit {

    loading: boolean = true;
    contactResult: any[] = [];

    constructor (
        public http: HttpClient,
        public dialog: MatDialog,
        public functions: FunctionsService,
        public dialogRef: MatDialogRef<ContactSearchModal>,
        public contactService: ContactService,
        public translate: TranslateService,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) { }

    ngOnInit(): void {
        this.contactResult = JSON.parse(JSON.stringify(this.data));
        this.loading = false;
    }

    formatContactAdress(contact: any) {
        if (this.functions.empty(contact.addressNumber) && this.functions.empty( contact.addressStreet) && this.functions.empty(contact.addressPostcode) && this.functions.empty(contact.addressTown) && this.functions.empty(contact.addressCountry)) {
            return null;
        } else {
            const addressArray = [];
            addressArray.push(contact.addressNumber, contact.addressStreet, contact.addressPostcode, contact.addressTown, contact.addressCountry);
            return addressArray.filter((item: any) => item !== '').join(' ');
        }
    }

    selectContact(contactIndex: number) {
        this.dialogRef.close(contactIndex);
    }
}