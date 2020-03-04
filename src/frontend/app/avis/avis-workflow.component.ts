import { Component, Input, OnInit, ElementRef, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { FunctionsService } from '../../service/functions.service';
import { tap, exhaustMap, map, startWith, catchError, finalize, filter } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { LatinisePipe } from 'ngx-pipes';
import { Observable, of } from 'rxjs';
import { MatDialog } from '@angular/material';
import { AddAvisModelModalComponent } from './addAvisModel/add-avis-model-modal.component';
import { ConfirmComponent } from '../../plugins/modal/confirm.component';

@Component({
    selector: 'app-avis-workflow',
    templateUrl: 'avis-workflow.component.html',
    styleUrls: ['avis-workflow.component.scss']
})
export class AvisWorkflowComponent implements OnInit {

    lang: any = LANG;
    avisWorkflow: any = {
        roles: ['sign', 'avis'],
        items: []
    };
    avisWorkflowClone: any = [];
    avisTemplates: any = {
        private: [],
        public: []
    };

    availableRoles: any[] = [];

    signAvisUsers: any = [];
    filteredSignAvisUsers: Observable<string[]>;
    filteredPublicModels: Observable<string[]>;
    filteredPrivateModels: Observable<string[]>;

    loading: boolean = false;
    avisModelListNotLoaded: boolean = true;
    data: any;

    @Input('injectDatas') injectDatas: any;
    @Input('adminMode') adminMode: boolean;
    @Input('resId') resId: number = null;

    @Input('showListModels') showListModels: boolean = true;

    @Input('mode') mode: 'parallel' | 'circuit' = 'circuit';

    @ViewChild('searchAvisUserInput', { static: false }) searchAvisUserInput: ElementRef;

    searchAvisUser = new FormControl();

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe,
        public dialog: MatDialog
    ) { }

    ngOnInit(): void {
        if (this.mode === 'parallel' && this.adminMode) {
            this.loadAvisRoles();
        }

        if (this.resId !== null) {
            if (this.mode === 'circuit') {
                this.loadWorkflow(this.resId);
            } else {
                this.loadParallelWorkflow(this.resId);
            }
        }
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            if (this.functions.empty(this.avisWorkflow.items[event.currentIndex].process_date)) {
                moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
            } else {
                this.notify.error(`${this.lang.moveAvisUserErr1} <b>${this.avisWorkflow.items[event.previousIndex].labelToDisplay}</b> ${this.lang.moveAvisUserErr2}.`);
            }
        }
    }

    loadAvisRoles() {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/roles`).pipe(
                tap((data: any) => {
                    this.availableRoles = data.roles.filter((role: any) => ['avis', 'avis_copy', 'avis_info'].indexOf(role.id) > -1).map((role: any) => {
                        return {
                            id: role.id,
                            label: role.label
                        }
                    });
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getRoleLabel(id: string) {
        return this.availableRoles.filter(role => role.id === id)[0].label;
    }

    loadListModel(entityId: number) {
        this.loading = true;

        this.avisWorkflow.items = [];

        this.http.get(`../../rest/listTemplates/entities/${entityId}?type=opinionCircuit`)
            .subscribe((data: any) => {
                if (data.listTemplates[0]) {
                    this.avisWorkflow.items = data.listTemplates[0].items.map((item: any) => {
                        return {
                            ...item,
                            item_entity: item.descriptionToDisplay,
                        }
                    });
                }
                this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items));
                this.loading = false;
            });
    }

    loadAvisUsersList() {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/autocomplete/users/circuit?circuit=opinion`).pipe(
                map((data: any) => {
                    data = data.map((user: any) => {
                        return {
                            id: user.id,
                            title: `${user.idToDisplay} (${user.otherInfo})`,
                            label: user.idToDisplay,
                            entity: user.otherInfo,
                            type: 'user'
                        }
                    });
                    return data;
                }),
                tap((data) => {
                    this.signAvisUsers = data;
                    this.filteredSignAvisUsers = this.searchAvisUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filter(value))
                        );
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async loadAvisModelList() {
        if (this.resId !== null) {
            await this.loadDefaultModel();
        }

        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/availableCircuits?circuit=opinion`).pipe(
                tap((data: any) => {
                    this.avisTemplates.public = this.avisTemplates.public.concat(data.circuits.filter((item: any) => !item.private).map((item: any) => {
                        return {
                            id: item.id,
                            title: item.title,
                            label: item.title,
                            type: 'entity'
                        }
                    }));

                    this.avisTemplates.private = data.circuits.filter((item: any) => item.private).map((item: any) => {
                        return {
                            id: item.id,
                            title: item.title,
                            label: item.title,
                            type: 'entity'
                        }
                    });
                    this.filteredPublicModels = this.searchAvisUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filterPublicModel(value))
                        );
                    this.filteredPrivateModels = this.searchAvisUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filterPrivateModel(value))
                        );
                    resolve(true);
                })
            ).subscribe();
        });
    }

    loadDefaultModel() {
        this.avisTemplates.public = [];

        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/resources/${this.resId}/defaultCircuit?circuit=opinion`).pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.itemsRemoved)) {
                        this.notify.error(this.lang.itemRemovedFromAvisTemplate + ' : ' + data.itemsRemoved.join(', '));
                    }
                }),
                filter((data: any) => !this.functions.empty(data.circuit)),
                tap((data: any) => {
                    if (!this.functions.empty(data.circuit)) {
                        this.avisTemplates.public.push({
                            id: data.circuit.id,
                            title: data.circuit.title,
                            label: data.circuit.title,
                            type: 'entity'
                        });
                    }
                }),
                finalize(() => resolve(true)),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async initFilterAvisModelList() {
        if (this.avisModelListNotLoaded) {
            await this.loadAvisUsersList();

            if (this.showListModels) {
                await this.loadAvisModelList();
            }

            this.searchAvisUser.reset();

            this.avisModelListNotLoaded = false;
        }
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.signAvisUsers.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.signAvisUsers;
        }
    }

    private _filterPrivateModel(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.avisTemplates.private.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.avisTemplates.private;
        }
    }

    private _filterPublicModel(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.avisTemplates.public.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.avisTemplates.public;
        }
    }

    loadWorkflow(resId: number) {
        this.resId = resId;
        this.loading = true;
        this.avisWorkflow.items = [];
        return new Promise((resolve, reject) => {
            this.http.get("../../rest/resources/" + resId + "/opinionCircuit").pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.itemsRemoved)) {
                        this.notify.error(this.lang.itemRemovedFromAvisTemplate + ' : ' + data.itemsRemoved.join(', '));
                    }
                }),
                filter((data: any) => !this.functions.empty(data.circuit)),
                tap((data: any) => {
                    data.circuit.forEach((element: any) => {
                        this.avisWorkflow.items.push(
                            {
                                ...element,
                                difflist_type: this.mode === 'circuit' ? 'AVIS_CIRCUIT' : 'entity_id'
                            });
                    });
                    this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items))
                }),
                finalize(() => {
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });

    }

    loadParallelWorkflow(resId: number) {
        this.resId = resId;
        this.loading = true;
        this.avisWorkflow.items = [];
        return new Promise((resolve, reject) => {
            this.http.get("../../rest/resources/" + resId + "/parallelOpinion")
                .subscribe((data: any) => {
                    data.forEach((element: any) => {
                        this.avisWorkflow.items.push(
                            {
                                ...element,
                                difflist_type: 'entity_id'
                            });
                    });
                    this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items));
                    this.loading = false;
                    resolve(true);
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        });

    }

    loadDefaultWorkflow(resId: number) {
        this.loading = true;
        this.avisWorkflow.items = [];
        this.http.get("../../rest/resources/" + resId + "/defaultCircuit?circuit=opinion").pipe(
            tap((data: any) => {
                if (!this.functions.empty(data.itemsRemoved)) {
                    this.notify.error(this.lang.itemRemovedFromAvisTemplate + ' : ' + data.itemsRemoved.join(', '));
                }
            }),
            filter((data: any) => !this.functions.empty(data.circuit)),
            tap((data: any) => {
                data.circuit.items.forEach((element: any) => {
                    this.avisWorkflow.items.push(
                        {
                            ...element,
                            difflist_type: this.mode === 'circuit' ? 'AVIS_CIRCUIT' : 'entity_id',
                            item_entity: element.descriptionToDisplay
                        });
                });
                this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items))
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deleteItem(index: number) {
        this.avisWorkflow.items.splice(index, 1);
    }

    getAvisCount() {
        return this.avisWorkflow.items.length;
    }

    changeRole(role: any, i: number) {
        this.avisWorkflow.items[i].item_mode = role.id;
    }

    getWorkflow() {
        return this.avisWorkflow.items;
    }

    getCurrentAvisUserIndex() {

        const index = this.avisWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastAvisUser().listinstance_id);

        return (index + 1);
    }

    getFirstAvisUser() {
        return !this.functions.empty(this.avisWorkflow.items[0]) ? this.avisWorkflow.items[0] : '';
    }

    getCurrentAvisUser() {

        const index = this.avisWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastAvisUser().listinstance_id);

        return !this.functions.empty(this.avisWorkflow.items[index + 1]) ? this.avisWorkflow.items[index + 1] : '';
    }

    getNextAvisUser() {

        const index = this.avisWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastAvisUser().listinstance_id);

        return !this.functions.empty(this.avisWorkflow.items[index + 2]) ? this.avisWorkflow.items[index + 2] : '';
    }

    getLastAvisUser() {
        let arrOnlyProcess = this.avisWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date));

        return !this.functions.empty(arrOnlyProcess[arrOnlyProcess.length - 1]) ? arrOnlyProcess[arrOnlyProcess.length - 1] : '';
    }

    saveAvisWorkflow(resIds: number[] = [this.resId]) {
        return new Promise((resolve, reject) => {
            if (this.avisWorkflow.items.length === 0) {
                this.http.delete(`../../rest/resources/${resIds[0]}/circuits/opinionCircuit`).pipe(
                    tap(() => {
                        this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items));
                        this.notify.success(this.lang.avisWorkflowDeleted);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            } else {
                const arrAvis = resIds.map(resId => {
                    return {
                        resId: resId,
                        listInstances: this.avisWorkflow.items
                    }
                });
                this.http.put(`../../rest/circuits/opinionCircuit`, { resources: arrAvis }).pipe(
                    tap((data: any) => {
                        this.avisWorkflowClone = JSON.parse(JSON.stringify(this.avisWorkflow.items));
                        this.notify.success(this.lang.avisWorkflowUpdated);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    addItemToWorkflow(item: any) {
        return new Promise((resolve, reject) => {
            if (item.type === 'user') {
                this.avisWorkflow.items.push({
                    item_id: item.id,
                    item_type: 'user',
                    item_entity: item.entity,
                    item_mode: 'avis',
                    labelToDisplay: item.label,
                    externalId: !this.functions.empty(item.externalId) ? item.externalId : null,
                    difflist_type: this.mode === 'circuit' ? 'AVIS_CIRCUIT' : 'entity_id'
                });
                this.searchAvisUser.reset();
                this.searchAvisUserInput.nativeElement.blur();
                resolve(true);
            } else if (item.type === 'entity') {
                this.http.get(`../../rest/listTemplates/${item.id}`).pipe(
                    tap((data: any) => {
                        this.avisWorkflow.items = this.avisWorkflow.items.concat(
                            data.listTemplate.items.filter((itemTemplate: any) => itemTemplate.hasPrivilege === true).map((itemTemplate: any) => {
                                return {
                                    item_id: itemTemplate.item_id,
                                    item_type: 'user',
                                    labelToDisplay: itemTemplate.idToDisplay,
                                    item_entity: itemTemplate.descriptionToDisplay,
                                    item_mode: 'avis',
                                    difflist_type: this.mode === 'circuit' ? 'AVIS_CIRCUIT' : 'entity_id'
                                }
                            })
                        );
                        this.searchAvisUser.reset();
                        this.searchAvisUserInput.nativeElement.blur();
                        resolve(true);
                    })
                ).subscribe();
            }
        });
    }

    resetWorkflow() {
        this.avisWorkflow.items = [];
    }

    emptyWorkflow() {
        return this.avisWorkflow.items.length === 0;
    }

    workflowEnd() {
        if (this.avisWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date)).length === this.avisWorkflow.items.length) {
            return true;
        } else {
            return false;
        }
    }

    openPromptSaveModel() {
        const dialogRef = this.dialog.open(AddAvisModelModalComponent, { data: { avisWorkflow: this.avisWorkflow.items } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => !this.functions.empty(data)),

            tap((data: any) => {
                this.avisTemplates.private.push({
                    id: data.id,
                    title: data.title,
                    label: data.title,
                    type: 'entity'
                });
                this.searchAvisUser.reset();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deletePrivateModel(model: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../../rest/listTemplates/${model.id}`)),
            tap(() => {
                this.avisTemplates.private = this.avisTemplates.private.filter((template: any) => template.id !== model.id);
                this.searchAvisUser.reset();
                this.notify.success(this.lang.modelDeleted);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getMaarchParapheurUserAvatar(externalId: string, key: number) {
        if (!this.functions.empty(externalId)) {
            this.http.get("../../rest/maarchParapheur/user/" + externalId + "/picture")
                .subscribe((data: any) => {
                    this.avisWorkflow.items[key].picture = data.picture;
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        }
    }

    isModified() {
        if (this.loading || JSON.stringify(this.avisWorkflow.items) === JSON.stringify(this.avisWorkflowClone)) {
            return false;
        } else {
            return true;
        }
    }
}
