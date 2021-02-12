import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { FormControl } from '@angular/forms';
import { debounceTime, switchMap, distinctUntilChanged, filter, tap, map, catchError } from 'rxjs/operators';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { SelectionModel } from '@angular/cdk/collections';
import { AppService } from '@service/app.service';
import { MaarchFlatTreeComponent } from '@plugins/tree/maarch-flat-tree.component';
import { MatAutocompleteTrigger } from '@angular/material/autocomplete';
import { of } from 'rxjs';

declare var $: any;

@Component({
    templateUrl: 'contacts-group-administration.component.html',
    styleUrls: [
        'contacts-group-administration.component.scss'
    ]
})
export class ContactsGroupAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    subMenus: any[] = [
        {
            icon: 'fa fa-book',
            route: '/administration/contacts',
            label: this.translate.instant('lang.contactsList'),
            current: false
        },
        {
            icon: 'fa fa-code',
            route: '/administration/contacts/contactsCustomFields',
            label: this.translate.instant('lang.customFieldsAdmin'),
            current: false
        },
        {
            icon: 'fa fa-cog',
            route: '/administration/contacts/contacts-parameters',
            label: this.translate.instant('lang.contactsParameters'),
            current: false
        },
        {
            icon: 'fa fa-users',
            route: '/administration/contacts/contacts-groups',
            label: this.translate.instant('lang.contactsGroups'),
            current: false
        },
        {
            icon: 'fas fa-magic',
            route: '/administration/contacts/duplicates',
            label: this.translate.instant('lang.duplicatesContactsAdmin'),
            current: false
        },
    ];

    creationMode: boolean;
    contactsGroup: any = {};
    nbCorrespondents: number;

    loading: boolean = false;
    initAutoCompleteContact = true;

    searchTerm: FormControl = new FormControl();
    searchResult: any = [];

    displayedColumns = ['select', 'type', 'contact', 'address'];
    displayedColumnsAdded = ['type', 'contact', 'address', 'actions'];
    dataSource: any;
    dataSourceLinkedCorrespondents: any;
    selection = new SelectionModel<Element>(true, []);

    @ViewChild('paginatorContactList', { static: true }) paginator: MatPaginator;
    @ViewChild('paginatorLinkedCorrespondents', { static: false }) paginatorLinkedCorrespondents: MatPaginator;
    @ViewChild('sortLinkedCorrespondents', { static: false }) sortLinkedCorrespondents: MatSort;
    @ViewChild('maarchTree', { static: true }) maarchTree: MaarchFlatTreeComponent;

    /** Selects all rows if they are not all selected; otherwise clear selection. */
    toggleAll() {
        this.dataSource.data.forEach((row: any) => {
            if (!$('#check_' + row.id + '-input').is(':disabled')) {
                this.selection.select(row.id);
            }
        });
        if (this.selection.selected.length > 0) {
            this.saveContactsList();
        }
    }

    applyFilter(filterValue: string) {
        this.dataSource = null;
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSourceLinkedCorrespondents.filter = filterValue;
    }

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        private viewContainerRef: ViewContainerRef
    ) {
        this.searchTerm.valueChanges.pipe(
            debounceTime(500),
            filter(value => value.length > 2),
            distinctUntilChanged(),
            switchMap(data => this.http.get('../rest/autocomplete/contacts', { params: { 'search': data } }))
        ).subscribe((response: any) => {
            this.searchResult = response;
            this.dataSource = new MatTableDataSource(this.searchResult);
            this.dataSource.paginator = this.paginator;
            // this.dataSource.sort      = this.sortContactList;
        });
    }

    ngOnInit(): void {
        this.loading = true;

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.route.params.subscribe(params => {

            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.translate.instant('lang.contactGroupCreation'));

                this.creationMode = true;
                this.contactsGroup.public = false;
                this.loading = false;
            } else {

                this.creationMode = false;

                this.http.get('../rest/contactsGroups/' + params['id'])
                    .subscribe((data: any) => {
                        this.contactsGroup = data.contactsGroup;
                        this.headerService.setHeader(this.translate.instant('lang.contactsGroupModification'), this.contactsGroup.label);
                        this.nbCorrespondents = this.contactsGroup.nbCorrespondentss;
                        this.dataSourceLinkedCorrespondents = new MatTableDataSource(this.contactsGroup.contacts);
                        this.loading = false;
                        setTimeout(() => {
                            this.dataSourceLinkedCorrespondents.paginator = this.paginatorLinkedCorrespondents;
                            this.dataSourceLinkedCorrespondents.sort = this.sortLinkedCorrespondents;
                        }, 0);
                    });
                this.initTree();
            }
        });
    }

    initTree() {
        // FOR TEST
        this.http.get('../rest/entities').pipe(
            map((data: any) => {
                data.entities = data.entities.map((entity: any) => {
                    return {
                        text: entity.entity_label,
                        icon: entity.icon,
                        parent_id: entity.parentSerialId,
                        id: entity.serialId,
                        state: {
                            opened: true
                        }
                    };
                });
                return data.entities;
            }),
            tap((entities: any) => {
                /*entities.forEach(element => {
                    if (this.availableEntities.indexOf(+element.id) > -1) {
                        element.state.disabled = false;
                    } else {
                        element.state.disabled = true;
                    }
                });*/
                this.maarchTree.initData(entities);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }


    searchContact(search: string) {
        this.http.get('../rest/autocomplete/contacts', { params: { 'search': search } }).pipe(
            tap((data: any) => {
                this.searchResult = data;
                this.dataSource = new MatTableDataSource(this.searchResult);
                this.dataSource.paginator = this.paginator;
            })
        ).subscribe();
    }

    saveContactsList(): void {
        this.http.post('../rest/contactsGroups/' + this.contactsGroup.id + '/contacts', { 'contacts': this.selection.selected })
            .subscribe((data: any) => {
                this.notify.success(this.translate.instant('lang.contactAdded'));
                this.nbCorrespondents = this.nbCorrespondents + this.selection.selected.length;
                this.selection.clear();
                this.contactsGroup = data.contactsGroup;
                setTimeout(() => {
                    this.dataSourceLinkedCorrespondents = new MatTableDataSource(this.contactsGroup.contacts);
                    this.dataSourceLinkedCorrespondents.paginator = this.paginatorLinkedCorrespondents;
                    this.dataSourceLinkedCorrespondents.sort = this.sortLinkedCorrespondents;
                }, 0);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    onSubmit() {
        if (this.creationMode) {
            this.http.post('../rest/contactsGroups', this.contactsGroup)
                .subscribe((data: any) => {
                    this.router.navigate(['/administration/contacts/contacts-groups/' + data.contactsGroup]);
                    this.notify.success(this.translate.instant('lang.contactsGroupAdded'));
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put('../rest/contactsGroups/' + this.contactsGroup.id, this.contactsGroup)
                .subscribe(() => {
                    this.router.navigate(['/administration/contacts-groups']);
                    this.notify.success(this.translate.instant('lang.contactsGroupUpdated'));

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    preDelete(row: any) {
        const r = confirm(this.translate.instant('lang.reallyWantToDeleteContactFromGroup'));

        if (r) {
            this.removeContact(this.contactsGroup.contacts[row], row);
        }
    }

    removeContact(contact: any, row: any) {
        this.http.delete('../rest/contactsGroups/' + this.contactsGroup.id + '/contacts/' + contact['id'])
            .subscribe(() => {
                const lastElement = this.contactsGroup.contacts.length - 1;
                this.contactsGroup.contacts[row] = this.contactsGroup.contacts[lastElement];
                this.contactsGroup.contacts[row].position = row;
                this.contactsGroup.contacts.splice(lastElement, 1);
                this.nbCorrespondents = this.nbCorrespondents - 1;
                this.dataSourceLinkedCorrespondents = new MatTableDataSource(this.contactsGroup.contacts);
                this.dataSourceLinkedCorrespondents.paginator = this.paginatorLinkedCorrespondents;
                this.dataSourceLinkedCorrespondents.sort = this.sortLinkedCorrespondents;
                this.notify.success(this.translate.instant('lang.contactDeletedFromGroup'));
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

    toggleCorrespondent(element: any) {
        if (!$('#check_' + element.id + '-input').is(':disabled')) {
            this.selection.toggle(element.id);
            this.saveContactsList();
        }
    }

    open(event: Event, trigger: MatAutocompleteTrigger) {
        event.stopPropagation();
        setTimeout(() => {
            trigger.openPanel();
        }, 100);
    }
}
