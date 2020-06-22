import { Component, OnInit, ViewChild, Inject, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { Router } from '@angular/router';
import { AppService } from '../../../service/app.service';
import { DiffusionsListComponent } from '../../diffusions/diffusions-list.component';
import { tap, catchError, filter, exhaustMap } from 'rxjs/operators';
import { FunctionsService } from '../../../service/functions.service';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { VisaWorkflowComponent } from '../../visa/visa-workflow.component';
import { AvisWorkflowComponent } from '../../avis/avis-workflow.component';
import { of } from 'rxjs/internal/observable/of';

declare var $: any;
@Component({
    templateUrl: 'entities-administration.component.html',
    styleUrls: ['entities-administration.component.css']
})
export class EntitiesAdministrationComponent implements OnInit {
    /*HEADER*/
    titleHeader: string;
    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    dialogRef: MatDialogRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    entities: any[] = [];
    listTemplateRoles: any[] = [];
    entityTypeList: any[] = [];
    currentEntity: any = {};
    isDraggable: boolean = true;
    newEntity: boolean = false;
    creationMode: boolean = false;
    visaCircuitModified: boolean = false;
    opinionCircuitModified: boolean = false;
    idVisaCircuit: number;
    idOpinionCircuit: number;
    config: any = {};

    dataSourceUsers = new MatTableDataSource(this.currentEntity.users);
    dataSourceTemplates = new MatTableDataSource(this.currentEntity.templates);
    displayedColumnsUsers = ['firstname', 'lastname'];
    displayedColumnsTemplates = ['template_label', 'template_target'];


    @ViewChild('paginatorUsers', { static: false }) paginatorUsers: MatPaginator;
    @ViewChild('paginatorTemplates', { static: false }) paginatorTemplates: MatPaginator;
    @ViewChild('tableUsers', { static: false }) sortUsers: MatSort;
    @ViewChild('tableTemplates', { static: false }) sortTemplates: MatSort;
    @ViewChild('appDiffusionsList', { static: false }) appDiffusionsList: DiffusionsListComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('appAvisWorkflow', { static: false }) appAvisWorkflow: AvisWorkflowComponent;
    applyFilterUsers(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSourceUsers.filter = filterValue;
    }

    applyFilterTemplates(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSourceTemplates.filter = filterValue;
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        private router: Router,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    async ngOnInit(): Promise<void> {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.entities);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        await this.getEntityTypes();
        await this.getRoles();
        await this.getEntities();

        this.loading = false;

        this.initEntitiesTree();

    }

    initEntitiesTree() {
        setTimeout(() => {
            $('#jstree').jstree({
                'checkbox': {
                    'deselect_all': true,
                    'three_state': false // no cascade selection
                },
                'core': {
                    force_text: true,
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'multiple': false,
                    'data': this.entities,
                    'check_callback': function (operation: any, node: any, node_parent: any, node_position: any, more: any) {
                        if (operation === 'move_node') {
                            if (node_parent.id === '#') {
                                return false;
                            } else if (!node_parent.original.allowed) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    }
                },
                'dnd': {
                    is_draggable: function (nodes: any) {
                        let i = 0;
                        const j = nodes.length;
                        for (; i < j; i++) {
                            if (!nodes[i].original.allowed) {
                                return false;
                            }
                        }
                        return true;
                    }
                },
                'plugins': ['checkbox', 'search', 'dnd', 'sort']
            });
            $('#jstree').jstree('select_node', this.entities[0]);
            let to: any = false;
            $('#jstree_search').keyup(function () {
                if (to) { clearTimeout(to); }
                to = setTimeout(function () {
                    const v: any = $('#jstree_search').val();
                    $('#jstree').jstree(true).search(v);
                }, 250);
            });
            $('#jstree')
                // listen for event
                .on('select_node.jstree', (e: any, data: any) => {
                    if (this.sidenavRight.opened === false) {
                        this.sidenavRight.open();
                    }
                    if (this.creationMode === true) {
                        this.currentEntity.parent_entity_id = data.node.id;
                    } else {
                        if (this.newEntity === true) {
                            this.loadEntity(this.currentEntity.entity_id);
                            this.newEntity = false;
                        } else {
                            this.loadEntity(data.node.id);
                        }
                    }

                }).on('deselect_node.jstree', (e: any, data: any) => {

                    this.sidenavRight.close();

                }).on('move_node.jstree', (e: any, data: any) => {

                    if (this.currentEntity.parent_entity_id !== this.currentEntity.entity_id) {
                        this.currentEntity.parent_entity_id = data.parent;
                    }
                    this.moveEntity();
                })
                // create the instance
                .jstree();

            $(document).on('dnd_start.vakata', (e: any, data: any) => {
                $('#jstree').jstree('deselect_all');
                $('#jstree').jstree('select_node', data.data.nodes[0]);
            });
        }, 0);
    }

    getEntityTypes() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/entityTypes`).pipe(
                tap((data: any) => {
                    this.entityTypeList = data['types'];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getRoles() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/listTemplates/types/entity_id/roles`).pipe(
                tap((data: any) => {
                    this.listTemplateRoles = data['roles'];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });

    }

    getEntities() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/entities`).pipe(
                tap((data: any) => {
                    this.entities = data['entities'];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });

    }

    loadEntity(entity_id: any) {
        this.visaCircuitModified = false;
        this.opinionCircuitModified = false;
        this.http.get('../rest/entities/' + entity_id + '/details')
            .subscribe((data: any) => {
                this.currentEntity = data['entity'];

                this.appDiffusionsList.loadListModel(this.currentEntity.id);
                this.appVisaWorkflow.loadListModel(this.currentEntity.id);
                this.appAvisWorkflow.loadListModel(this.currentEntity.id);

                if (this.currentEntity.visaCircuit) {
                    this.idVisaCircuit = this.currentEntity.visaCircuit.id;
                } else {
                    this.idVisaCircuit = null;
                }
                if (this.currentEntity.opinionCircuit) {
                    this.idOpinionCircuit = this.currentEntity.opinionCircuit.id;
                } else {
                    this.idOpinionCircuit = null;
                }
                this.dataSourceUsers = new MatTableDataSource(this.currentEntity.users);
                this.dataSourceUsers.paginator = this.paginatorUsers;
                this.dataSourceUsers.sort = this.sortUsers;

                this.dataSourceTemplates = new MatTableDataSource(this.currentEntity.templates);
                this.dataSourceTemplates.paginator = this.paginatorTemplates;
                this.dataSourceTemplates.sort = this.sortTemplates;

                if (!this.currentEntity.listTemplate.items) {
                    this.currentEntity.listTemplate.items = [];
                }
                this.listTemplateRoles.forEach((role: any) => {
                    if (role.available && !this.currentEntity.listTemplate.items[role.id]) {
                        this.currentEntity.listTemplate.items[role.id] = [];
                    }
                });
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    addElemListModelVisa(element: any) {
        this.visaCircuitModified = true;
        const newElemListModel = {
            'id': element.id,
            'type': 'user',
            'mode': 'sign',
            'idToDisplay': element.idToDisplay,
            'descriptionToDisplay': element.otherInfo
        };

        if (!this.currentEntity.visaCircuit.items) {
            this.currentEntity.visaCircuit.items = [];
        }
        this.currentEntity.visaCircuit.items.push(newElemListModel);
        if (this.currentEntity.visaCircuit.items.length > 1) {
            this.currentEntity.visaCircuit.items[this.currentEntity.visaCircuit.items.length - 2].mode = 'visa';
        }
    }

    addElemListModelOpinion(element: any) {
        this.opinionCircuitModified = true;
        const newElemListModel = {
            'id': element.id,
            'type': 'user',
            'mode': 'avis',
            'idToDisplay': element.idToDisplay,
            'descriptionToDisplay': element.otherInfo
        };

        if (!this.currentEntity.opinionCircuit.items) {
            this.currentEntity.opinionCircuit.items = [];
        }
        this.currentEntity.opinionCircuit.items.push(newElemListModel);
    }

    saveEntity() {
        if (this.currentEntity.parent_entity_id === '#') {
            this.currentEntity.parent_entity_id = '';
        }

        let r = true;
        if (this.currentEntity.parent_entity_id === '') {
            r = confirm(this.lang.entityWithoutParentMessage);
        }

        if (r) {
            if (this.creationMode) {
                this.http.post('../rest/entities', this.currentEntity)
                    .subscribe((data: any) => {
                        this.currentEntity.listTemplate = [];
                        this.entities = data['entities'];
                        this.creationMode = false;
                        this.newEntity = true;
                        $('#jstree').jstree(true).settings.core.data = this.entities;
                        // $('#jstree').jstree(true).settings.select_node = this.currentEntity;
                        $('#jstree').jstree(true).refresh();
                        $('#jstree').on('refresh.jstree', (e: any) => {
                            $('#jstree').jstree('deselect_all');
                            $('#jstree').jstree('select_node', this.currentEntity.entity_id);
                        });
                        this.notify.success(this.lang.entityAdded);
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });
            } else {
                this.http.put('../rest/entities/' + this.currentEntity.entity_id, this.currentEntity)
                    .subscribe((data: any) => {
                        this.entities = data['entities'];
                        $('#jstree').jstree(true).settings.core.data = this.entities;
                        $('#jstree').jstree('refresh');
                        this.notify.success(this.lang.entityUpdated);
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });
            }
        }
    }

    moveEntity() {
        this.http.put('../rest/entities/' + this.currentEntity.entity_id, this.currentEntity)
            .subscribe(() => {
                this.notify.success(this.lang.entityUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    readMode() {
        this.creationMode = false;
        this.isDraggable = true;
        $('#jstree').jstree('deselect_all');
        if (this.currentEntity.parent_entity_id) {
            for (let i = 0; i < this.entities.length; i++) {
                if (this.entities[i].entity_id === this.currentEntity.parent_entity_id) {
                    $('#jstree').jstree('select_node', this.entities[i]);
                    break;
                }
            }
        } else {
            this.sidenavRight.close();
        }
    }

    selectParentEntity(entity_id: any) {
        if (this.creationMode) {
            $('#jstree').jstree('deselect_all');
            $('#jstree').jstree('select_node', entity_id);
        }
    }

    removeEntity() {
        if (this.currentEntity.documents > 0 || this.currentEntity.redirects > 0 || this.currentEntity.instances > 0 || this.currentEntity.users.length > 0 || this.currentEntity.templates.length > 0) {
            this.config = { panelClass: 'maarch-modal', data: { entity: this.currentEntity } };
            this.dialogRef = this.dialog.open(EntitiesAdministrationRedirectModalComponent, this.config);
            this.dialogRef.afterClosed().subscribe((result: any) => {
                if (result) {
                    if (this.currentEntity.listTemplate.id) {
                        this.http.delete('../rest/listTemplates/' + this.currentEntity.listTemplate.id)
                            .subscribe((data: any) => {
                                this.currentEntity.listTemplate.id = data.id;
                                this.http.get('../rest/listTemplates/types/entity_id/roles')
                                    .subscribe((dataTemplates: any) => {
                                        this.listTemplateRoles = dataTemplates['roles'];
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });
                            }, (err) => {
                                this.notify.error(err.error.errors);
                            });
                    }

                    if (this.idVisaCircuit) {
                        this.http.delete('../rest/listTemplates/' + this.idVisaCircuit)
                            .subscribe(() => {
                                this.idVisaCircuit = null;
                            }, (err) => {
                                this.notify.error(err.error.errors);
                            });
                    }

                    this.http.put('../rest/entities/' + result.entity_id + '/reassign/' + result.redirectEntity, {})
                        .subscribe((data: any) => {
                            this.entities = data['entities'];
                            $('#jstree').jstree(true).settings.core.data = this.entities;
                            $('#jstree').jstree('refresh');
                            this.sidenavRight.close();

                            if (typeof data['deleted'] !== 'undefined' && !data['deleted']) {
                                this.notify.success(this.lang.entityDeletedButAnnuaryUnreachable);
                            } else {
                                this.notify.success(this.lang.entityDeleted);
                            }
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                }
                this.dialogRef = null;
            });
        } else {
            const r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + this.currentEntity.entity_label + ' »');

            if (r) {
                if (this.currentEntity.listTemplate.id) {
                    this.http.delete('../rest/listTemplates/' + this.currentEntity.listTemplate.id)
                        .subscribe((data: any) => {
                            this.currentEntity.listTemplate.id = data.id;
                            this.http.get('../rest/listTemplates/types/entity_id/roles')
                                .subscribe((dataTemplates: any) => {
                                    this.listTemplateRoles = dataTemplates['roles'];
                                }, (err) => {
                                    this.notify.error(err.error.errors);
                                });
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                }

                if (this.idVisaCircuit) {
                    this.http.delete('../rest/listTemplates/' + this.idVisaCircuit)
                        .subscribe(() => {
                            this.idVisaCircuit = null;
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                }

                this.http.delete('../rest/entities/' + this.currentEntity.entity_id)
                    .subscribe((data: any) => {
                        this.entities = data['entities'];
                        $('#jstree').jstree(true).settings.core.data = this.entities;
                        $('#jstree').jstree('refresh');
                        this.sidenavRight.close();
                        if (typeof data['deleted'] !== 'undefined' && !data['deleted']) {
                            this.notify.success(this.lang.entityDeletedButAnnuaryUnreachable);
                        } else {
                            this.notify.success(this.lang.entityDeleted);
                        }
                    }, (err: any) => {
                        this.notify.error(err.error.errors);
                    });
            }
        }

    }

    prepareEntityAdd() {
        this.creationMode = true;
        this.isDraggable = false;
        if (this.currentEntity.entity_id) {
            for (let i = 0; i < this.entities.length; i++) {
                if (this.entities[i].entity_id === this.currentEntity.entity_id) {
                    this.currentEntity = { 'entity_type': this.entityTypeList[0].id };
                    this.currentEntity.parent_entity_id = this.entities[i].entity_id;
                    break;
                }
            }
        } else {
            this.currentEntity = { 'entity_type': this.entityTypeList[0].id };
            $('#jstree').jstree('deselect_all');
            this.sidenavRight.open();
            /*for (let i = 0; i < this.entities.length; i++) {
                if (this.entities[i].allowed == true) {
                    $('#jstree').jstree('select_node', this.entities[i]);
                    break;
                }
            }*/
        }
    }

    updateStatus(entity: any, method: string) {
        this.http.put('../rest/entities/' + entity['entity_id'] + '/status', { 'method': method })
            .subscribe((data: any) => {
                this.notify.success('');
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    saveDiffList() {
        const newDiffList = {
            'title': this.currentEntity.entity_id,
            'description': this.currentEntity.entity_id,
            'type': 'diffusionList',
            'entityId': this.currentEntity.id,
            'items': this.appDiffusionsList.getCurrentListinstance().map((item: any) => {
                return {
                    'id': item.item_id,
                    'type': item.item_type,
                    'mode': item.item_mode
                };
            })
        };

        if (!this.functions.empty(this.currentEntity.listTemplate.id)) {
            this.http.put(`../rest/listTemplates/${this.currentEntity.listTemplate.id}`, newDiffList).pipe(
                tap(() => {
                    this.notify.success(this.lang.diffusionModelUpdated);
                    this.appDiffusionsList.loadListModel(this.currentEntity.id);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.post(`../rest/listTemplates?admin=true`, newDiffList).pipe(
                tap((data: any) => {
                    this.currentEntity.listTemplate.id = data.id;
                    this.notify.success(this.lang.diffusionModelUpdated);
                    this.appDiffusionsList.loadListModel(this.currentEntity.id);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    deleteDiffList() {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../rest/listTemplates/${this.currentEntity.listTemplate.id}`)),
            tap(() => {
                this.currentEntity.listTemplate.id = null;
                this.notify.success(this.lang.diffusionModelDeleted);
                this.appDiffusionsList.loadListModel(this.currentEntity.id);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    saveDiffListVisa() {
        const newDiffList = {
            'title': this.currentEntity.entity_id,
            'description': this.currentEntity.entity_id,
            'type': 'visaCircuit',
            'entityId': this.currentEntity.id,
            'items': this.appVisaWorkflow.getWorkflow().map((item: any, index: number) => {
                return {
                    'id': item.item_id,
                    'type': item.item_type,
                    'mode': item.requested_signature ? 'sign' : 'visa',
                    'sequence': index
                };
            })
        };
        if (!this.appVisaWorkflow.isValidWorkflow()) {
            this.notify.error(this.appVisaWorkflow.getError());
        } else {
            if (this.functions.empty(newDiffList.items)) {
                this.http.delete(`../rest/listTemplates/${this.idVisaCircuit}`).pipe(
                    tap(() => {
                        this.idVisaCircuit = null;
                        this.notify.success(this.lang.diffusionModelDeleted);
                        this.appVisaWorkflow.loadListModel(this.currentEntity.id);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            } else if (!this.functions.empty(this.idVisaCircuit)) {
                this.http.put(`../rest/listTemplates/${this.idVisaCircuit}`, newDiffList).pipe(
                    tap(() => {
                        this.notify.success(this.lang.diffusionModelUpdated);
                        this.appVisaWorkflow.loadListModel(this.currentEntity.id);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            } else {
                this.http.post(`../rest/listTemplates?admin=true`, newDiffList).pipe(
                    tap((data: any) => {
                        this.idVisaCircuit = data.id;
                        this.notify.success(this.lang.diffusionModelUpdated);
                        this.appVisaWorkflow.loadListModel(this.currentEntity.id);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        }
    }

    saveDiffListOpinion() {
        const newDiffList = {
            'title': this.currentEntity.entity_id,
            'description': this.currentEntity.entity_id,
            'type': 'opinionCircuit',
            'entityId': this.currentEntity.id,
            'items': this.appAvisWorkflow.getWorkflow().map((item: any, index: number) => {
                return {
                    'id': item.item_id,
                    'type': item.item_type,
                    'mode': 'avis',
                    'sequence': index
                };
            })
        };

        if (this.functions.empty(newDiffList.items)) {
            this.http.delete(`../rest/listTemplates/${this.idOpinionCircuit}`).pipe(
                tap(() => {
                    this.idOpinionCircuit = null;
                    this.notify.success(this.lang.diffusionModelDeleted);
                    this.appAvisWorkflow.loadListModel(this.currentEntity.id);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (!this.functions.empty(this.idOpinionCircuit)) {
            this.http.put(`../rest/listTemplates/${this.idOpinionCircuit}`, newDiffList).pipe(
                tap(() => {
                    this.notify.success(this.lang.diffusionModelUpdated);
                    this.appAvisWorkflow.loadListModel(this.currentEntity.id);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.post(`../rest/listTemplates?admin=true`, newDiffList).pipe(
                tap((data: any) => {
                    this.idOpinionCircuit = data.id;
                    this.notify.success(this.lang.diffusionModelUpdated);
                    this.appAvisWorkflow.loadListModel(this.currentEntity.id);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    removeDiffListOpinion(template: any, i: number): any {
        this.opinionCircuitModified = true;
        this.currentEntity.opinionCircuit.items.splice(i, 1);
    }

    toggleRole(role: any) {
        if (role.usedIn.length > 0) {
            const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, data: { title: this.lang.confirmAction, msg: this.lang.roleUsedInTemplateInfo + ' : <b>' + role.usedIn.join(', ') + '</b><br/>' + this.lang.roleUsedInTemplateInfo2 } });

            dialogRef.afterClosed().subscribe(result => {
                if (result === 'ok') {
                    role.available = !role.available;
                    this.http.put('../rest/listTemplates/types/entity_id/roles', { 'roles': this.listTemplateRoles })
                        .subscribe(() => {
                            role.usedIn = [];
                            if (this.currentEntity.listTemplate) {
                                this.currentEntity.listTemplate.items[role.id] = [];
                            }
                            this.notify.success(this.lang.listTemplatesRolesUpdated);
                        }, (err) => {
                            this.notify.error(err.error.errors);
                        });
                }
            });
        } else {
            role.available = !role.available;
            this.http.put('../rest/listTemplates/types/entity_id/roles', { 'roles': this.listTemplateRoles })
                .subscribe(() => {
                    if (this.currentEntity.listTemplate) {
                        this.currentEntity.listTemplate.items[role.id] = [];
                        this.http.get('../rest/listTemplates/types/entity_id/roles')
                            .subscribe((data: any) => {
                                this.listTemplateRoles = data['roles'];
                            }, (err) => {
                                this.notify.error(err.error.errors);
                            });
                    }
                    this.notify.success(this.lang.listTemplatesRolesUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    linkUser(newUser: any) {
        const entity = {
            'entityId': this.currentEntity.entity_id,
            'role': ''
        };

        this.http.post('../rest/users/' + newUser.id + '/entities', entity)
            .subscribe((data: any) => {
                const displayName = newUser.idToDisplay.split(' ');
                const user = {
                    id: newUser.id,
                    user_id: newUser.otherInfo,
                    firstname: displayName[0],
                    lastname: displayName[1]
                };
                this.currentEntity.users.push(user);
                this.dataSourceUsers = new MatTableDataSource(this.currentEntity.users);
                this.dataSourceUsers.paginator = this.paginatorUsers;
                this.dataSourceUsers.sort = this.sortUsers;
                this.notify.success(this.lang.userAdded);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    showTemplate(templateId: any) {
        if (this.currentEntity.canAdminTemplates) {
            this.router.navigate(['/administration/templates/' + templateId]);
        }
    }

    addEntityToAnnuary() {
        this.http.put('../rest/entities/' + this.currentEntity.id + '/annuaries', this.currentEntity)
            .subscribe((data: any) => {
                this.currentEntity.business_id = data['entitySiret'];
                if (typeof data['synchronized'] === 'undefined') {
                    this.notify.success(this.lang.siretGenerated);
                } else {
                    if (data['synchronized']) {
                        this.notify.success(this.lang.siretGeneratedAndSynchronizationDone);
                    } else {
                        this.notify.success(this.lang.siretGeneratedButAnnuaryUnreachable);
                    }
                }
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }
}
@Component({
    templateUrl: 'entities-administration-redirect-modal.component.html',
    styles: [
        '.alert-message { max-width: inherit; }'
    ]
})
export class EntitiesAdministrationRedirectModalComponent {
    lang: any = LANG;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<EntitiesAdministrationRedirectModalComponent>) {
        console.log(this.data.entity.redirectEntity);
    }

    setRedirectEntity(entity: any) {
        this.data.entity.redirectEntity = entity.id;
    }
}
