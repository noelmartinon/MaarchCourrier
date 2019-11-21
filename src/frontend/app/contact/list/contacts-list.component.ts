import { Component, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

@Component({
    selector: 'app-contacts-list',
    templateUrl: 'contacts-list.component.html',
    styleUrls: ['contacts-list.component.scss'],
    providers: [NotificationService]
})
export class ContactsListComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = true;

    contacts: any = [];

    /**
     * Ressource identifier to load contact List
     */
    @Input('resId') resId: number;
    @Input('mode') mode: 'recipients' | 'senders';

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
    ) { }

    ngOnInit(): void {
        this.http.get(`../../rest/resources/${this.resId}/contacts?type=${this.mode}`).pipe(
            tap((data: any) => {
                this.contacts = data.contacts;
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    goTo(contact: any) {
        window.open(`https://www.google.com/maps/search/${contact.num}+${contact.street},+${contact.postalCode}+${contact.town},+${contact.country}`,'_blank')
    }
}