import { Component, OnInit, ViewChild, EventEmitter, Inject, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { HeaderService } from '../../../../service/header.service';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../../../../service/app.service';
import { Observable, merge, Subject, of as observableOf, of } from 'rxjs';
import { MatPaginator, MatSort, MatDialog, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { takeUntil, startWith, switchMap, map, catchError, filter, exhaustMap, tap, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ConfirmComponent } from '../../../../plugins/modal/confirm.component';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../../../service/functions.service';

@Component({
    selector: 'contact-list',
    templateUrl: "contacts-list-administration.component.html",
    styleUrls: ['contacts-list-administration.component.scss'],
    providers: [AppService]
})
export class ContactsListAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    filtersChange = new EventEmitter();

    data: any;

    displayedColumnsContact: string[] = ['filling', 'firstname', 'lastname', 'company', 'formatedAddress', 'actions'];

    isLoadingResults = true;
    routeUrl: string = '../../rest/contacts';
    resultListDatabase: ContactListHttpDao | null;
    resultsLength = 0;

    searchContact = new FormControl();
    search: string = '';
    dialogRef: MatDialogRef<any>;

    @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
    @ViewChild('tableContactListSort', { static: true }) sort: MatSort;

    private destroy$ = new Subject<boolean>();

    subMenus: any[] = [
        {
            icon: 'fas fa-magic',
            route: '/administration/contacts/duplicates',
            label: this.lang.duplicatesContactsAdmin,
            current: false
        },
        {
            icon: 'fa fa-book',
            route: '/administration/contacts/list',
            label: this.lang.contactsList,
            current: true
        },
        {
            icon: 'fa fa-code',
            route: '/administration/contacts/contactsCustomFields',
            label: this.lang.customFieldsAdmin,
            current: false
        },
        {
            icon: 'fa fa-cog',
            route: '/administration/contacts/contacts-parameters',
            label: this.lang.contactsParameters,
            current: false
        },
        {
            icon: 'fa fa-users',
            route: '/administration/contacts/contacts-groups',
            label: this.lang.contactsGroups,
            current: false
        },
    ];

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef) { }
        

    ngOnInit(): void {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.loading = true;
        this.initContactList();
        this.initAutocompleteContacts();
    }

    initContactList() {
        this.resultListDatabase = new ContactListHttpDao(this.http);
        this.paginator.pageIndex = 0;
        this.sort.active = 'lastname';
        this.sort.direction = 'asc';
        this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

        // When list is refresh (sort, page, filters)
        merge(this.sort.sortChange, this.paginator.page, this.filtersChange)
            .pipe(
                takeUntil(this.destroy$),
                startWith({}),
                switchMap(() => {
                    this.isLoadingResults = true;
                    return this.resultListDatabase!.getRepoIssues(
                        this.sort.active, this.sort.direction, this.paginator.pageIndex, this.routeUrl, this.search);
                }),
                map(data => {
                    this.isLoadingResults = false;
                    data = this.processPostData(data);
                    this.resultsLength = data.count;
                    this.headerService.setHeader(this.lang.administration + ' ' + this.lang.contacts.toLowerCase(), '', '');
                    return data.contacts;
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    this.isLoadingResults = false;
                    return observableOf([]);
                })
            ).subscribe(data => this.data = data);
    }

    processPostData(data: any) {
        data.contacts.forEach((element: any) => {
            let tmpFormatedAddress = [];
            tmpFormatedAddress.push(element.addressNumber);
            tmpFormatedAddress.push(element.addressStreet);
            tmpFormatedAddress.push(element.addressPostcode);
            tmpFormatedAddress.push(element.addressTown);
            tmpFormatedAddress.push(element.addressCountry);
            element.formatedAddress = tmpFormatedAddress.filter(address => !this.isEmptyValue(address)).join(' ');
        });

        if (!this.functions.empty(data.contacts[0]) && !this.functions.empty(data.contacts[0].filling)) {
            this.displayedColumnsContact = ['filling', 'firstname', 'lastname', 'company', 'formatedAddress', 'actions'];
        } else {
            this.displayedColumnsContact = ['firstname', 'lastname', 'company', 'formatedAddress', 'actions'];
        }
        return data;
    }

    deleteContact(contact: any) {

        if (contact.isUsed) {
            this.dialogRef = this.dialog.open(ContactsListAdministrationRedirectModalComponent, { panelClass: 'maarch-modal', autoFocus: false });
            this.dialogRef.afterClosed().subscribe((result: any) => {
                if (typeof result != "undefined" && result != '') {
                    var queryparams = '';
                    if (result.processMode == 'reaffect') {
                        queryparams = '?redirect=' + result.contactId;
                    }
                    this.http.request('DELETE', `../../rest/contacts/${contact.id}${queryparams}`)
                        .subscribe(() => {
                            this.refreshDao();
                            this.notify.success(this.lang.contactDeleted);
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                }
                this.dialogRef = null;
            });
        } else {
            const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });
            dialogRef.afterClosed().pipe(
                filter((data: string) => data === 'ok'),
                exhaustMap(() => this.http.delete(`../../rest/contacts/${contact.id}`)),
                tap((data: any) => {
                    this.refreshDao();
                    this.notify.success(this.lang.contactDeleted);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    toggleContact(contact: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.suspend, msg: this.lang.confirmAction } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.put(`../../rest/contacts/${contact.id}/activation`, { enabled: !contact.enabled })),
            tap((data: any) => {
                this.refreshDao();
                if (!contact.enabled === true) {
                    this.notify.success(this.lang.contactEnabled);
                } else {
                    this.notify.success(this.lang.contactDisabled);
                }
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    refreshDao() {
        this.filtersChange.emit();
    }

    initAutocompleteContacts() {
        this.searchContact.valueChanges
            .pipe(
                tap((value) => {
                    if (value.length === 0) {
                        this.search = '';
                        this.paginator.pageIndex = 0;
                        this.refreshDao();
                    }
                }),
                debounceTime(300),
                filter(value => value.length > 2),
                distinctUntilChanged(),
                tap((data) => {
                    this.search = data;
                    this.paginator.pageIndex = 0;
                    this.refreshDao();
                }),
            ).subscribe();
    }

    isEmptyValue(value: string) {

        if (value === null) {
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
}

export interface ContactList {
    contacts: any[];
    count: number;
}
export class ContactListHttpDao {

    constructor(private http: HttpClient) { }

    getRepoIssues(sort: string, order: string, page: number, href: string, search: string): Observable<ContactList> {

        let offset = page * 10;
        const requestUrl = `${href}?limit=10&offset=${offset}&order=${order}&orderBy=${sort}&search=${search}`;

        return this.http.get<ContactList>(requestUrl);
    }
}
@Component({
    templateUrl: "contacts-list-administration-redirect-modal.component.html",
    styleUrls: [],
    providers: [NotificationService]
})
export class ContactsListAdministrationRedirectModalComponent {
    lang: any = LANG;
    modalTitle: string = this.lang.confirmAction;
    redirectContact: number;
    processMode: string = 'delete';

    constructor(
        public http: HttpClient,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ContactsListAdministrationRedirectModalComponent>,
        private notify: NotificationService) {
    }

    ngOnInit(): void {

    }

    setRedirectUser(contact: any) {
        this.redirectContact = contact.id;
    }
}
