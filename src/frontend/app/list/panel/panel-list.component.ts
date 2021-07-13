import { Component, OnInit, ViewChild, EventEmitter, Output } from '@angular/core';
import { LANG } from '../../translate.component';
import { DiffusionsListComponent } from '../../diffusions/diffusions-list.component';
import { VisaWorkflowComponent } from '../../visa/visa-workflow.component';
import { AvisWorkflowComponent } from '../../avis/avis-workflow.component';
import { NotesListComponent } from '../../notes/notes.component';
import { AttachmentsListComponent } from '../../attachments/attachments-list.component';

declare function $j(selector: any): any;

@Component({
    selector: 'app-panel-list',
    templateUrl: "panel-list.component.html",
    styleUrls: ['panel-list.component.scss'],
})
export class PanelListComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = false;

    selectedDiffusionTab: number = 0;
    injectDatasParam = {
        resId: 0,
        editable: false
    };

    mode: string;
    icon: string;
    currentResource: any = {};

    @Output('refreshBadgeNotes') refreshBadgeNotes = new EventEmitter<string>();
    @Output('refreshBadgeAttachments') refreshBadgeAttachments = new EventEmitter<string>();

    @ViewChild('appDiffusionsList') appDiffusionsList: DiffusionsListComponent;
    @ViewChild('appVisaWorkflow') appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('appAvisWorkflow') appAvisWorkflow: AvisWorkflowComponent;
    @ViewChild('appNotesList') appNotesList: NotesListComponent;
    @ViewChild('appAttachmentsList') appAttachmentsList: AttachmentsListComponent;

    constructor() { }

    ngOnInit(): void { }

    loadComponent(mode: string, data: any) {

        this.mode = mode;
        this.currentResource = data;

        this.injectDatasParam.resId = this.currentResource.res_id;

        if (mode == 'diffusion') {
            setTimeout(() => {
                this.icon = 'fa-sitemap';
                this.selectedDiffusionTab = 0;
                this.injectDatasParam.resId = this.currentResource.res_id;
                this.appDiffusionsList.loadListinstance(this.currentResource.res_id);
                this.appVisaWorkflow.loadWorkflow(this.currentResource.res_id);
                this.appAvisWorkflow.loadWorkflow(this.currentResource.res_id);
            }, 0);

        } else if (mode == 'note') {
            setTimeout(() => {
                this.icon = 'fa-comments';
                this.appNotesList.loadNotes(this.currentResource.res_id);
            }, 0);

            setTimeout(() => {
                $j('textarea').focus();
            }, 200);
        } else if (mode == 'attachment') {
            setTimeout(() => {
                this.icon = 'fa-paperclip';
                this.appAttachmentsList.loadAttachments(this.currentResource.res_id);
            }, 0);
        }
    }

    reloadBadgeNotes(nb:any) {
        this.refreshBadgeNotes.emit(nb);
    }

    reloadBadgeAttachments(nb:any) {
        this.refreshBadgeAttachments.emit(nb);
    }
}
