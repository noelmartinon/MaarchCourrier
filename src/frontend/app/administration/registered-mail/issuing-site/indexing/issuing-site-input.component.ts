import { Component, OnInit, Input, ViewChild, ElementRef, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { FormControl } from '@angular/forms';
import { tap, catchError, finalize } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';
import { NotificationService } from '../../../../../service/notification/notification.service';
import { HeaderService } from '../../../../../service/header.service';
import { FunctionsService } from '../../../../../service/functions.service';

@Component({
    selector: 'app-issuing-site-input',
    templateUrl: 'issuing-site-input.component.html',
    styleUrls: [
        'issuing-site-input.component.scss',
        '../../../../indexation/indexing-form/indexing-form.component.scss'
    ],
})

export class IssuingSiteInputComponent implements OnInit {

    loading: boolean = false;

    issuingSiteList: any[] = [];

    issuingSiteAddress: any = null;

    /**
     * FormControl used when autocomplete is used in form and must be catched in a form control.
     */
    @Input() control: FormControl = new FormControl('');
    @Input() registedMailType: string = null;


    @Output() afterSelected = new EventEmitter<string>();

    @ViewChild('autoCompleteInput', { static: true }) autoCompleteInput: ElementRef;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public functions: FunctionsService
    ) {

    }

    ngOnInit() {
        this.getIssuingSites();
        if (!this.functions.empty(this.control.value)) {
            setTimeout(() => {
                this.setAddress(this.control.value);
            }, 0);
        }
    }

    getIssuingSites() {
        this.loading = true;
        this.http.get(`../rest/registeredMail/sites`).pipe(
            tap((data: any) => {
                this.issuingSiteAddress = null;
                this.issuingSiteList = data['sites'].filter((item: any) => item.entities.indexOf(this.headerService.user.entities[0].id) > -1).map((item: any) => {
                    return {
                        ...item,
                        id: item.id,
                        label: `${item.label} (${item.accountNumber})`
                    };
                });
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    setAddress(id: any) {
        this.http.get(`../rest/registeredMail/sites/${id}`).pipe(
            tap((data: any) => {
                this.issuingSiteAddress = data['site'];
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    goTo() {
        window.open(`https://www.google.com/maps/search/${this.issuingSiteAddress.addressNumber}+${this.issuingSiteAddress.addressStreet},+${this.issuingSiteAddress.addressPostcode}+${this.issuingSiteAddress.addressTown},+${this.issuingSiteAddress.addressCountry}`, '_blank');
    }
}
