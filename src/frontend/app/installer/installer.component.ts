import { Component, OnInit, ViewChild, AfterViewInit, ViewChildren } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { HeaderService } from '../../service/header.service';
import { NotificationService } from '../../service/notification/notification.service';
import { STEPPER_GLOBAL_OPTIONS } from '@angular/cdk/stepper';
import { MatStepper } from '@angular/material/stepper';
import { AppService } from '../../service/app.service';
import { LANG } from '../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { SortPipe } from '../../plugins/sorting.pipe';
import { StepAction } from './types';
import { MatDialog } from '@angular/material/dialog';
import { InstallActionComponent } from './install-action/install-action.component';
import { filter } from 'rxjs/internal/operators/filter';
import { tap } from 'rxjs/internal/operators/tap';
import { finalize } from 'rxjs/internal/operators/finalize';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { FunctionsService } from '../../service/functions.service';
import { InstallerService } from './installer.service';

@Component({
    templateUrl: './installer.component.html',
    styleUrls: ['./installer.component.scss'],
    providers: [
        {
            provide: STEPPER_GLOBAL_OPTIONS, useValue: { showError: true },
        },
        SortPipe
    ]
})
export class InstallerComponent implements OnInit, AfterViewInit {

    lang: any = LANG;

    @ViewChildren('stepContent') stepContent: any;
    @ViewChild('stepper', { static: true }) stepper: MatStepper;

    constructor(
        private translate: TranslateService,
        private http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        private notify: NotificationService,
        public appService: AppService,
        private sortPipe: SortPipe,
        public dialog: MatDialog,
        private functionService: FunctionsService,
        private installerService: InstallerService
    ) { }

    ngOnInit(): void {
        this.headerService.hideSideBar = true;
    }

    ngAfterViewInit(): void {
        $('.mat-horizontal-stepper-header-container').insertBefore('.bg-head-content');
        $('.mat-step-icon').css('background-color', 'white');
        $('.mat-step-icon').css('color', '#135f7f');
        $('.mat-step-label').css('color', 'white');
        /*$('.mat-step-label').css('opacity', '0.5');
        $('.mat-step-label-active').css('opacity', '1');*/
        /*$('.mat-step-label-selected').css('font-size', '160%');
        $('.mat-step-label-selected').css('transition', 'all 0.2s');
        $('.mat-step-label').css('transition', 'all 0.2s');*/
    }

    isValidStep() {
        return false;
    }

    initStep(ev: any) {
        this.stepContent.toArray()[ev.selectedIndex].initStep();
    }

    nextStep() {
        this.stepper.next();
    }

    endInstall() {
        let installContent: StepAction[] = [];
        this.stepContent.toArray().forEach((component: any) => {
            installContent = installContent.concat(component.getInfoToInstall());
        });

        installContent = this.sortPipe.transform(installContent, 'installPriority');

        console.log(installContent);

        // this.stepper.next();

        const dialogRef = this.dialog.open(InstallActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: installContent
        });
        dialogRef.afterClosed().pipe(
            filter((result: any) => !this.functionService.empty(result)),
            tap((result: any) => {
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

}
