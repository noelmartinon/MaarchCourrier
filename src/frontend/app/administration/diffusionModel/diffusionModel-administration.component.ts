import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../../../service/app.service';
import { tap, catchError, finalize } from 'rxjs/operators';
import { of } from 'rxjs';
import { VisaWorkflowComponent } from '../../visa/visa-workflow.component';
import { AvisWorkflowComponent } from '../../avis/avis-workflow.component';

@Component({
    templateUrl: "diffusionModel-administration.component.html",
    styleUrls: ['diffusionModel-administration.component.scss'],
    providers: [AppService]
})
export class DiffusionModelAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;

    lang: any = LANG;
    loading: boolean = true;

    diffusionModel: any = {
        title: '',
        description: '',
        type: 'opinionCircuit',
        items: []
    };
    diffusionModelClone: any = {};
    idCircuit: number;
    itemTypeList: any = [
        {
            id: "visaCircuit",
            label: this.lang.visaCircuit
        },
        {
            id: "opinionCircuit",
            label: this.lang.opinionCircuit
        }
    ];
    creationMode: boolean;
    listDiffModified: boolean = false;

    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('appAvisWorkflow', { static: false }) appAvisWorkflow: AvisWorkflowComponent;

    constructor(
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService
    ) { }

    ngOnInit(): void {

        this.loading = true;

        this.route.params.subscribe(async params => {
            
            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.diffusionModelCreation);

                this.creationMode = true;
                this.loading = false;

            } else {

                this.creationMode = false;

                await this.getTemplate(params['id']);

                if (this.diffusionModel.type === 'visaCircuit') {
                    this.loadVisaCircuit();
                } else {
                    this.loadOpinionCircuit();
                }
            }
        });
    }

    getTemplate(id: number) {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/listTemplates/${id}`).pipe(
                tap((data: any) => {
                    this.diffusionModel = data.listTemplate;
                    this.diffusionModel.id = id;
                    resolve(true);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe()
        });
    }

    loadVisaCircuit() {
        const item = {
            id: this.diffusionModel.id,
            type: 'entity'
        };

        setTimeout(async () => {
            this.appVisaWorkflow.resetWorkflow();
            await this.appVisaWorkflow.addItemToWorkflow(item, false);
            this.diffusionModel.items = this.appVisaWorkflow.getWorkflow();
            this.diffusionModelClone = JSON.parse(JSON.stringify(this.diffusionModel));            
        }, 0);
    }

    loadOpinionCircuit() {
        const item = {
            id: this.diffusionModel.id,
            type: 'entity'
        };
        setTimeout(async () => {
            this.appAvisWorkflow.resetWorkflow();
            await this.appAvisWorkflow.addItemToWorkflow(item);
            this.diffusionModel.items = this.appAvisWorkflow.getWorkflow();
            this.diffusionModelClone = JSON.parse(JSON.stringify(this.diffusionModel)); 
        }, 0);
    }

    onSubmit() {
        if (this.creationMode) {
            this.createTemplate();
        } else {
            this.updateTemplate();
        }
    }

    createTemplate() {
        this.http.post("../rest/listTemplates?admin=true", this.formatCircuit()).pipe(
            tap(() => {
                this.router.navigate(["/administration/diffusionModels"]);
                this.notify.success(this.lang.diffusionModelAdded);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateTemplate() {
        this.http.put(`../rest/listTemplates/${this.diffusionModel.id}`, this.formatCircuit()).pipe(
            tap(() => {
                this.router.navigate(["/administration/diffusionModels"]);
                this.notify.success(this.lang.diffusionModelUpdated);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatCircuit() {
        if (this.diffusionModel.type === 'visaCircuit') {
            this.diffusionModel.items = this.appVisaWorkflow.getWorkflow().map((item: any, index: number) => {
                return {
                    "id": item.item_id,
                    "type": "user",
                    "mode": item.requested_signature ? 'sign' : 'visa',
                    "sequence": index
                }
            });
            return this.diffusionModel;
        } else {
            this.diffusionModel.items = this.appAvisWorkflow.getWorkflow().map((item: any, index: number) => {
                return {
                    "id": item.item_id,
                    "type": "user",
                    "mode": 'avis',
                    "sequence": index
                }
            });
            return this.diffusionModel;
        }   
    }

    checkPrivileges(items: any) {
        var isValid = true;

        items.forEach((item: any) => {
            if (!item.hasPrivilege) {
                isValid = false;
            }
        });
        return isValid;
    }

    isValidForm() {
        if (this.diffusionModel.type === 'visaCircuit') {
            return this.appVisaWorkflow !== undefined && this.appVisaWorkflow.getWorkflow().length > 0 && this.diffusionModel.title !== '' && this.checkPrivileges(this.appVisaWorkflow.getWorkflow());
        } else {
            return this.appAvisWorkflow !== undefined && this.appAvisWorkflow.getWorkflow().length > 0 && this.diffusionModel.title !== '' && this.checkPrivileges(this.appAvisWorkflow.getWorkflow());
        }
    }

    cancelModification() {
        this.diffusionModel = JSON.parse(JSON.stringify(this.diffusionModelClone));
        if (this.diffusionModel.type === 'visaCircuit') {
            this.loadVisaCircuit();
        } else {
            this.loadOpinionCircuit();
        }
    }
}
