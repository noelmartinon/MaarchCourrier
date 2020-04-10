import { Component, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { ContactService } from '../../../service/contact.service';

@Component({
    selector: 'app-contacts-list',
    templateUrl: 'contacts-list.component.html',
    styleUrls: ['contacts-list.component.scss'],
    providers: [ContactService]
})
export class ContactsListComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = true;

    contacts: any = [];

    customFields: any[] = [];

    /**
     * Ressource identifier to load contact List
     */
    @Input() resId: number = null;

    /**
     * [Filter to load specific contact Type]
     * use with @resId
     */
    @Input() mode: 'recipients' | 'senders' = 'recipients';

    /**
     * [Id of contact to load a specific resource]
     * DO NOT USE with @resId
     * ex : {id: 1, type: 'contact'}
     */
    @Input() contact: any = null;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private contactService: ContactService,
    ) { }

    async ngOnInit(): Promise<void> {

        await this.getCustomFields();

        if (this.resId !== null) {
            this.loadContactsOfResource(this.resId, this.mode);
        } else if (this.contact !== null) {
            this.loadContact(this.contact.id, this.contact.type);
        }
    }

    getCustomFields() {
        return new Promise((resolve, reject) => {
            this.http.get("../rest/contactsCustomFields").pipe(
                tap((data: any) => {
                    this.customFields = data.customFields.map((custom: any) => {
                        return {
                            id: custom.id,
                            label: custom.label
                        }
                    });
                    resolve(true);
                })
            ).subscribe();
        });
    }

    loadContactsOfResource(resId: number, mode: string) {
        this.http.get(`../rest/resources/${resId}/contacts?type=${mode}`).pipe(
            tap((data: any) => {
                this.contacts = data.contacts.map((contact: any) => {
                    return {
                        ...contact,
                        civility: this.contactService.formatCivilityObject(contact.civility),
                        fillingRate: this.contactService.formatFillingObject(contact.fillingRate),
                        customFields: !this.empty(contact.customFields) ? this.formatCustomField(contact.customFields) : [],
                    }
                });
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    loadContact(contactId: number, type: string) {
        this.contacts = [];

        if (type === 'contact') {
            this.http.get('../rest/contacts/' + contactId).pipe(
                tap((contact: any) => {
                    this.contacts[0] = {
                        ...contact,
                        civility: this.contactService.formatCivilityObject(contact.civility),
                        fillingRate: this.contactService.formatFillingObject(contact.fillingRate),
                        customFields: !this.empty(contact.customFields) ? this.formatCustomField(contact.customFields) : [],
                        type: 'contact'
                    };
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (type === 'user') {
            this.http.get('../rest/users/' + contactId).pipe(
                tap((data: any) => {
                    this.contacts[0] = {
                        type: 'user',
                        civility: this.contactService.formatCivilityObject(null),
                        fillingRate: this.contactService.formatFillingObject(null),
                        customFields: [],
                        firstname: data.firstname,
                        lastname: data.lastname,
                    };
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (type === 'entity') {
            this.http.get('../rest/entities/' + contactId).pipe(
                tap((data: any) => {
                    this.contacts[0] = {
                        type: 'entity',
                        civility: this.contactService.formatCivilityObject(null),
                        fillingRate: this.contactService.formatFillingObject(null),
                        customFields: [],
                        lastname: data.short_label,
                    };
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.error(err.error.errors);
                    return of(false);
                })
            ).subscribe();
        }
    }

    formatCustomField(data: any) {
        let arrCustomFields: any[] = [];

        Object.keys(data).forEach(element => {
            arrCustomFields.push({
                label: this.customFields.filter(custom => custom.id == element).length > 0 ? this.customFields.filter(custom => custom.id == element)[0].label : element,
                value: data[element]
            });
        });

        return arrCustomFields;
    }

    goTo(contact: any) {
        window.open(`https://www.google.com/maps/search/${contact.addressNumber}+${contact.addressStreet},+${contact.addressPostcode}+${contact.addressTown},+${contact.addressCountry}`, '_blank')
    }

    empty(value: any) {
        if (value === null || value === undefined) {
            return true;

        } else if (Array.isArray(value)) {
            if (value.length > 0) {
                return false;
            } else {
                return true;
            }
        } else if (String(value) !== '') {
            return false;
        } else {
            return true;
        }
    }

    emptyOtherInfo(contact: any) {

        if (contact.type === 'contact' && (!this.empty(contact.communicationMeans) || !this.empty(contact.customFields))) {
            return false;
        } else {
            return true;
        }
    }
}
