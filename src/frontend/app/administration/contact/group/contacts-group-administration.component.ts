import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { HeaderService }        from '../../../../service/header.service';
import { FormControl } from '@angular/forms';
import { debounceTime, switchMap, distinctUntilChanged, filter } from 'rxjs/operators';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { SelectionModel } from '@angular/cdk/collections';
import { AppService } from '../../../../service/app.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "contacts-group-administration.component.html",
    styleUrls: [
        'contacts-group-administration.component.scss'
    ],
    providers: [AppService]
})
export class ContactsGroupAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight  : MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;

    subMenus:any [] = [
        {
            icon: 'fa fa-book',
            route: '/administration/contacts/list',
            label : this.lang.contactsList,
            current: false
        },
        {
            icon: 'fa fa-code',
            route: '/administration/contacts/contactsCustomFields',
            label : this.lang.customFieldsAdmin,
            current: false
        },
        {
            icon: 'fa fa-cog',
            route: '/administration/contacts/contacts-parameters',
            label : this.lang.contactsParameters,
            current: false
        },
        {
            icon: 'fa fa-users',
            route: '/administration/contacts/contacts-groups',
            label : this.lang.contactsGroups,
            current: false
        },
    ];

    creationMode: boolean;
    contactsGroup: any = {};
    nbContact   : number;

    loading: boolean = false;
    initAutoCompleteContact = true;

    searchTerm: FormControl = new FormControl();
    searchResult: any = [];

    displayedColumns = ['select', 'contact', 'address'];
    displayedColumnsAdded = ['contact', 'address', 'actions'];
    dataSource: any;
    dataSourceAdded: any;
    selection = new SelectionModel<Element>(true, []);

    /** Selects all rows if they are not all selected; otherwise clear selection. */
    masterToggle(event: any) {
        if (event.checked) {
            this.dataSource.data.forEach((row: any) => {
                if (!$j("#check_" + row.id + '-input').is(":disabled")) {
                    this.selection.select(row.id);
                }
            });
        } else {
            this.selection.clear();
        }
    }

    @ViewChild('paginatorContactList', { static: true }) paginator: MatPaginator;
    //@ViewChild('tableContactList', { static: true }) sortContactList: MatSort;

    @ViewChild('paginatorAdded', { static: true }) paginatorAdded: MatPaginator;
    @ViewChild('tableAdded', { static: true }) sortAdded: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSourceAdded.filter = filterValue;
    }

    constructor(
        public http: HttpClient, 
        private route: ActivatedRoute, 
        private router: Router, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService,
        private viewContainerRef: ViewContainerRef
    ) {
        $j("link[href='merged_css.php']").remove();

        this.searchTerm.valueChanges.pipe(
            debounceTime(500),
            filter(value => value.length > 2),
            distinctUntilChanged(),
            switchMap(data => this.http.get('../../rest/autocomplete/contacts', { params: { "search": data } }))
        ).subscribe((response: any) => {
            this.searchResult = response;
            this.dataSource = new MatTableDataSource(this.searchResult);
            this.dataSource.paginator = this.paginator;
            //this.dataSource.sort      = this.sortContactList;
        });  
    }

    ngOnInit(): void {
        this.loading = true;

        this.route.params.subscribe(params => {
            this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.contactGroupCreation);
                
                this.creationMode = true;
                this.contactsGroup.public = false;
                this.loading = false;
            } else {
                
                this.creationMode = false;

                this.http.get('../../rest/contactsGroups/' + params['id'])
                .subscribe((data: any) => {
                        this.contactsGroup = data.contactsGroup;
                        this.headerService.setHeader(this.lang.contactsGroupModification, this.contactsGroup.label);
                        this.nbContact = this.contactsGroup.nbContacts;
                        setTimeout(() => {
                            this.dataSourceAdded = new MatTableDataSource(this.contactsGroup.contacts);
                            this.dataSourceAdded.paginator = this.paginatorAdded;
                            this.dataSourceAdded.sort = this.sortAdded;
                        }, 0);

                        this.loading = false;
                    });
            }
        });
    }

    saveContactsList(elem: any): void {
        elem.textContent = this.lang.loading + '...';
        elem.disabled = true;
        this.http.post('../../rest/contactsGroups/' + this.contactsGroup.id + '/contacts', { 'contacts': this.selection.selected })
            .subscribe((data: any) => {
                this.notify.success(this.lang.contactAdded);
                this.nbContact = this.nbContact + this.selection.selected.length;
                this.selection.clear();
                elem.textContent = this.lang.add;
                this.contactsGroup = data.contactsGroup;
                setTimeout(() => {
                    this.dataSourceAdded = new MatTableDataSource(this.contactsGroup.contacts);
                    this.dataSourceAdded.paginator = this.paginatorAdded;
                    this.dataSourceAdded.sort = this.sortAdded;
                }, 0);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    onSubmit() {
        if (this.creationMode) {
            this.http.post('../../rest/contactsGroups', this.contactsGroup)
                .subscribe((data: any) => {
                    this.router.navigate(['/administration/contacts/contacts-groups/' + data.contactsGroup]);
                    this.notify.success(this.lang.contactsGroupAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put('../../rest/contactsGroups/' + this.contactsGroup.id, this.contactsGroup)
                .subscribe(() => {
                    this.router.navigate(['/administration/contacts-groups']);
                    this.notify.success(this.lang.contactsGroupUpdated);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    preDelete(row: any) {
        let r = confirm(this.lang.reallyWantToDeleteContactFromGroup);

        if (r) {
            this.removeContact(this.contactsGroup.contacts[row], row);
        }
    }

    removeContact(contact: any, row: any) {
        this.http.delete("../../rest/contactsGroups/" + this.contactsGroup.id + "/contacts/" + contact['id'])
            .subscribe(() => {
                var lastElement = this.contactsGroup.contacts.length - 1;
                this.contactsGroup.contacts[row] = this.contactsGroup.contacts[lastElement];
                this.contactsGroup.contacts[row].position = row;
                this.contactsGroup.contacts.splice(lastElement, 1);
                this.nbContact = this.nbContact - 1;
                this.dataSourceAdded = new MatTableDataSource(this.contactsGroup.contacts);
                this.dataSourceAdded.paginator = this.paginatorAdded;
                this.dataSourceAdded.sort = this.sortAdded;
                this.notify.success(this.lang.contactDeletedFromGroup);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    launchLoading() {
        if (this.searchTerm.value.length > 2) {
            this.dataSource = null;
            this.initAutoCompleteContact = false;
        }
    }

    isInGrp(contact: any): boolean {
        let isInGrp = false;
        this.contactsGroup.contacts.forEach((row: any) => {
            if (row.id == contact.id) {
                isInGrp = true;
            }
        });
        return isInGrp;
    }

    selectContact(id:any) {
        if (!$j("#check_" + id + '-input').is(":disabled")) {
            this.selection.toggle(id);
        }    
    }
}
