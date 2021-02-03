import { Component, Input, OnInit, ElementRef, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { FunctionsService } from '@service/functions.service';
import { tap, catchError, finalize, filter } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { LatinisePipe, ScanPipe } from 'ngx-pipes';
import { Observable, of } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';
import { ActivatedRoute } from '@angular/router';
import { PrivilegeService } from '@service/privileges.service';

@Component({
    selector: 'app-external-visa-workflow',
    templateUrl: 'external-visa-workflow.component.html',
    styleUrls: ['external-visa-workflow.component.scss'],
    providers: [ScanPipe]
})
export class ExternalVisaWorkflow implements OnInit {

    visaWorkflow: any = {
        roles: ['sign', 'visa'],
        items: []
    };
    visaWorkflowClone: any = [];
    visaTemplates: any = {
        private: [],
        public: []
    };

    signVisaUsers: any = [];
    filteredPrivateModels: Observable<string[]>;

    loading: boolean = false;
    data: any;

    @Input('injectDatas') injectDatas: any;
    @Input('target') target: string = '';
    @Input('adminMode') adminMode: boolean;
    @Input('resId') resId: number = null;
    @Input('showComment') showComment: boolean = true;
    @Input('linkedToMaarchParapheur') linkedToMaarchParapheur: boolean = false;

    @Output() workflowUpdated = new EventEmitter<any>();


    searchVisaSignUser = new FormControl();

    loadedInConstructor: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe,
        public dialog: MatDialog,
        private route: ActivatedRoute,
        private privilegeService: PrivilegeService
    ) {
        // ngOnInit is not called if navigating in the same component : must be in constructor for this case
        this.route.params.subscribe(params => {
            this.loading = true;

            this.resId = params['resId'];

            if (!this.functions.empty(this.resId)) {
                this.loadedInConstructor = true;
                // this.loadWorkflow(this.resId);
            } else {
                this.loadedInConstructor = false;
            }

        }, (err: any) => {
            this.notify.handleErrors(err);
        });
    }

    ngOnInit(): void {
        if (!this.functions.empty(this.resId) && !this.loadedInConstructor) {
            // this.initFilterVisaModelList();
            // this.loadWorkflow(this.resId);
        } else {
            this.loading = false;
        }
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            if (this.linkedToMaarchParapheur) {
                if (this.canMoveUserExtParaph(event)) {
                    moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
                } else {
                    this.notify.error(this.translate.instant('lang.errorUserSignType'));
                }
            } else {
                if (this.canManageUser(this.visaWorkflow.items[event.currentIndex], event.currentIndex)) {
                    moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
                    this.workflowUpdated.emit(event.container);
                } else {
                    this.notify.error(this.translate.instant('lang.moveVisaUserErr', { value1: this.visaWorkflow.items[event.previousIndex].labelToDisplay }));
                }
            }
        }
    }

    canMoveUserExtParaph(ev: any) {
        const newWorkflow = this.array_move(this.visaWorkflow.items.slice(), ev.currentIndex, ev.previousIndex);
        const res = this.isValidExtWorkflow(newWorkflow);
        return res;
    }

    array_move(arr: any, old_index: number, new_index: number) {
        if (new_index >= arr.length) {
            let k = new_index - arr.length + 1;
            while (k--) {
                arr.push(undefined);
            }
        }
        arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
        return arr; // for testing
    }

    isValidExtWorkflow(workflow: any = this.visaWorkflow) {
        let res: boolean = true;
        workflow.forEach((item: any, indexUserRgs: number) => {
            if (['visa', 'stamp'].indexOf(item.role) === -1) {
                if (workflow.filter((itemUserStamp: any, indexUserStamp: number) => indexUserStamp > indexUserRgs && itemUserStamp.role === 'stamp').length > 0) {
                    res = false;
                }
            }
        });
        return res;
    }

    loadListModel(entityId: number) {
        this.loading = true;

        this.visaWorkflow.items = [];

        const route = `../rest/listTemplates/entities/${entityId}?type=visaCircuit&maarchParapheur=true`;

        return new Promise((resolve) => {
            this.http.get(route)
                .subscribe((data: any) => {
                    if (data.listTemplates[0]) {
                        this.visaWorkflow.items = data.listTemplates[0].items.map((item: any) => {
                            return {
                                ...item,
                                item_entity: item.descriptionToDisplay,
                                requested_signature: item.item_mode !== 'visa',
                                currentRole: item.item_mode
                            };
                        });
                    }
                    this.visaWorkflow.items.forEach((element: any, key: number) => {
                        if (!this.functions.empty(element['externalId'])) {
                            this.getMaarchParapheurUserAvatar(element.externalId.maarchParapheur, key);
                        }
                    });
                    this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                    this.loading = false;
                    resolve(true);
                });
        });
    }

    loadDefaultModel() {
        this.visaTemplates.public = [];

        return new Promise((resolve, reject) => {
            this.http.get(`../rest/resources/${this.resId}/defaultCircuit?circuit=visa`).pipe(
                filter((data: any) => !this.functions.empty(data.circuit)),
                tap((data: any) => {
                    if (!this.functions.empty(data.circuit)) {
                        this.visaTemplates.public.push({
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

    loadWorkflowMaarchParapheur(attachmentId: number, type: string) {
        this.loading = true;
        this.visaWorkflow.items = [];
        this.http.get(`../rest/documents/${attachmentId}/maarchParapheurWorkflow?type=${type}`)
            .subscribe((data: any) => {
                data.workflow.forEach((element: any, key: any) => {
                    const user = {
                        'listinstance_id': key,
                        'id': element.userId,
                        'labelToDisplay': element.userDisplay,
                        'requested_signature': element.mode !== 'visa',
                        'process_date': this.functions.formatFrenchDateToTechnicalDate(element.processDate),
                        'picture': '',
                        'hasPrivilege': true,
                        'isValid': true,
                        'delegatedBy': null,
                        'role': element.signatureMode,
                        'status': element.status
                    };
                    this.visaWorkflow.items.push(user);
                    this.http.get('../rest/maarchParapheur/user/' + element.userId + '/picture')
                        .subscribe((data: any) => {
                            this.visaWorkflow.items.filter((item: any) => item.id === element.userId)[0].picture = data.picture;
                        }, (err: any) => {
                            this.notify.handleErrors(err);
                        });
                });
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    deleteItem(index: number) {
        this.visaWorkflow.items.splice(index, 1);
        this.workflowUpdated.emit(this.visaWorkflow.items);
    }

    getVisaCount() {
        return this.visaWorkflow.items.length;
    }

    getWorkflow() {
        return this.visaWorkflow.items;
    }

    getCurrentVisaUserIndex() {
        if (this.getLastVisaUser().listinstance_id === undefined) {
            const index = 0;
            return this.getRealIndex(index);
        } else {
            let index = this.visaWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastVisaUser().listinstance_id);
            index++;
            return this.getRealIndex(index);
        }
    }

    getFirstVisaUser() {
        return !this.functions.empty(this.visaWorkflow.items[0]) && this.visaWorkflow.items[0].isValid ? this.visaWorkflow.items[0] : '';
    }

    getNextVisaUser() {
        let index = this.getCurrentVisaUserIndex();
        index = index + 1;
        const realIndex = this.getRealIndex(index);

        return !this.functions.empty(this.visaWorkflow.items[realIndex]) ? this.visaWorkflow.items[realIndex] : '';
    }

    getLastVisaUser() {
        const arrOnlyProcess = this.visaWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date) && item.isValid);

        return !this.functions.empty(arrOnlyProcess[arrOnlyProcess.length - 1]) ? arrOnlyProcess[arrOnlyProcess.length - 1] : '';
    }

    getRealIndex(index: number) {
        while (index < this.visaWorkflow.items.length && !this.visaWorkflow.items[index].isValid) {
            index++;
        }
        return index;
    }

    checkExternalSignatoryBook() {
        return this.visaWorkflow.items.filter((item: any) => this.functions.empty(item.externalId)).map((item: any) => item.labelToDisplay);
    }

    saveVisaWorkflow(resIds: number[] = [this.resId]) {
        return new Promise((resolve, reject) => {
            if (this.visaWorkflow.items.length === 0) {
                this.http.delete(`../rest/resources/${resIds[0]}/circuits/visaCircuit`).pipe(
                    tap(() => {
                        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                        this.notify.success(this.translate.instant('lang.visaWorkflowDeleted'));
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
            } else if (this.isValidWorkflow()) {
                const arrVisa = resIds.map(resId => {
                    return {
                        resId: resId,
                        listInstances: this.visaWorkflow.items
                    };
                });
                this.http.put(`../rest/circuits/visaCircuit`, { resources: arrVisa }).pipe(
                    tap((data: any) => {
                        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                        this.notify.success(this.translate.instant('lang.visaWorkflowUpdated'));
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
            } else {
                this.notify.error(this.getError());
                resolve(false);
            }
        });
    }

    addItemToWorkflow(item: any, maarchParapheurMode = false) {
        return new Promise((resolve, reject) => {
            if (maarchParapheurMode) {
                // const requestedSignature = !this.functions.empty(item.requested_signature) ? item.requested_signature : false;
                this.visaWorkflow.items.push({
                    item_id: item.id,
                    item_type: 'user',
                    item_entity: item.email,
                    labelToDisplay: item.idToDisplay,
                    externalId: item.externalId,
                    difflist_type: 'VISA_CIRCUIT',
                    signatory: !this.functions.empty(item.signatory) ? item.signatory : false,
                    hasPrivilege: true,
                    isValid: true,
                    availableRoles : ['visa'].concat(item.signatureModes),
                    role: item.signatureModes[item.signatureModes.length - 1]
                });
                if (!this.isValidRole(this.visaWorkflow.items.length - 1, item.signatureModes[item.signatureModes.length - 1], item.signatureModes[item.signatureModes.length - 1])) {
                    this.visaWorkflow.items[this.visaWorkflow.items.length - 1].role = 'visa';
                }

                if (this.linkedToMaarchParapheur) {
                    this.getMaarchParapheurUserAvatar(item.externalId.maarchParapheur, this.visaWorkflow.items.length - 1);
                }
                this.searchVisaSignUser.reset();
                resolve(true);
            } else if (item.type === 'user') {
                const requestedSignature = !this.functions.empty(item.requested_signature) ? item.requested_signature : false;
                this.visaWorkflow.items.push({
                    item_id: item.id,
                    item_type: 'user',
                    item_entity: item.entity,
                    labelToDisplay: item.label,
                    externalId: !this.functions.empty(item.externalId) ? item.externalId : null,
                    difflist_type: 'VISA_CIRCUIT',
                    signatory: !this.functions.empty(item.signatory) ? item.signatory : false,
                    requested_signature: requestedSignature,
                    hasPrivilege: item.hasPrivilege,
                    isValid: item.isValid,
                    currentRole: requestedSignature ? 'sign' : 'visa'
                });

                if (this.linkedToMaarchParapheur) {
                    this.getMaarchParapheurUserAvatar(item.externalId.maarchParapheur, this.visaWorkflow.items.length - 1);
                }
                this.searchVisaSignUser.reset();
                this.workflowUpdated.emit(this.visaWorkflow.items);
                resolve(true);
            } else if (item.type === 'entity') {
                this.http.get(`../rest/listTemplates/${item.id}`).pipe(
                    tap((data: any) => {
                        this.visaWorkflow.items = this.visaWorkflow.items.concat(

                            data.listTemplate.items.map((itemTemplate: any) => {
                                return {
                                    item_id: itemTemplate.item_id,
                                    item_type: 'user',
                                    labelToDisplay: itemTemplate.idToDisplay,
                                    item_entity: itemTemplate.descriptionToDisplay,
                                    difflist_type: 'VISA_CIRCUIT',
                                    signatory: false,
                                    requested_signature: itemTemplate.item_mode === 'sign',
                                    hasPrivilege: itemTemplate.hasPrivilege,
                                    isValid: itemTemplate.isValid,
                                    currentRole: itemTemplate.item_mode
                                };
                            })
                        );
                        this.searchVisaSignUser.reset();
                        resolve(true);
                    })
                ).subscribe();
            }
        });
    }

    resetWorkflow() {
        this.visaWorkflow.items = [];
    }

    isValidWorkflow() {
        if ((this.visaWorkflow.items.filter((item: any) => item.requested_signature).length > 0 && this.visaWorkflow.items.filter((item: any) => (!item.hasPrivilege || !item.isValid) && (item.process_date === null || this.functions.empty(item.process_date))).length === 0) && this.visaWorkflow.items.length > 0) {
            return true;
        } else {
            return false;
        }
    }

    getError() {
        if (this.visaWorkflow.items.filter((item: any) => item.requested_signature).length === 0) {
            return this.translate.instant('lang.signUserRequired');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.hasPrivilege).length > 0) {
            return this.translate.instant('lang.mustDeleteUsersWithNoPrivileges');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.isValid && (item.process_date === null || this.functions.empty(item.process_date))).length > 0) {
            return this.translate.instant('lang.mustDeleteInvalidUsers');
        }
    }

    emptyWorkflow() {
        return this.visaWorkflow.items.length === 0;
    }

    workflowEnd() {
        if (this.visaWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date)).length === this.visaWorkflow.items.length) {
            return true;
        } else {
            return false;
        }
    }

    getMaarchParapheurUserAvatar(externalId: string, key: number) {
        if (!this.functions.empty(externalId)) {
            this.http.get('../rest/maarchParapheur/user/' + externalId + '/picture')
                .subscribe((data: any) => {
                    this.visaWorkflow.items[key].picture = data.picture;
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        }
    }

    isModified() {
        return !(this.loading || JSON.stringify(this.visaWorkflow.items) === JSON.stringify(this.visaWorkflowClone));
    }

    canManageUser(item: any, i: number) {
        if (this.adminMode) {
            if (!this.functions.empty(item.process_date)) {
                return false;
            } else if (this.target === 'signatureBook' && this.getCurrentVisaUserIndex() === i) {
                return this.privilegeService.hasCurrentUserPrivilege('modify_visa_in_signatureBook');
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    isValidRole(indexWorkflow: any, role: string, currentRole: string) {
        if (this.visaWorkflow.items.filter((item: any, index: any) => index > indexWorkflow && ['stamp'].indexOf(item.role) > -1).length > 0 && ['visa', 'stamp'].indexOf(currentRole) > -1 && ['visa', 'stamp'].indexOf(role) === -1) {
            return false;
        } else if (this.visaWorkflow.items.filter((item: any, index: any) => index < indexWorkflow && ['visa', 'stamp'].indexOf(item.role) === -1).length > 0 && role === 'stamp') {
            return false;
        } else {
            return true;
        }
    }
}
