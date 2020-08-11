import { Injectable } from '@angular/core';
import { LANG } from '../app/translate.component';
import { TranslateService } from '@ngx-translate/core';
import { JoyrideService } from 'ngx-joyride';
import { LocalStorageService } from './local-storage.service';
import { HeaderService } from './header.service';
import { FunctionsService } from './functions.service';
import { Router } from '@angular/router';

@Injectable({
    providedIn: 'root'
})
export class FeatureTourService {

    lang: any = LANG;

    currentStepType: string = '';

    currentTour: any = null;

    tour: any[] = [
        {
            type: 'welcome',
            stepId: 'welcome',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.welcomeTourTitle')}</b>`,
            description: this.translate.instant('lang.welcomeTourDescription'),
            redirectToAdmin: false,
        },
        {
            type: 'email',
            stepId: 'admin_email_server@administration',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.admin_email_serverTitle')}</b>`,
            description: this.translate.instant('lang.admin_email_serverTour'),
            redirectToAdmin: false,
        },
        {
            type: 'email',
            stepId: 'emailTour@administration/sendmail',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.emailTourTitle')}</b>`,
            description: this.translate.instant('lang.emailTourDescription'),
            redirectToAdmin: false,
        },
        {
            type: 'email',
            stepId: 'emailTour2@administration/sendmail',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.emailTour2Title')}</b>`,
            description: this.translate.instant('lang.emailTour2Description'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'admin_notif@administration',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.admin_notifTitle')}</b>`,
            description: this.translate.instant('lang.admin_notifTour'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'BASKETS_Tour@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.notifTour2Title')}</b>`,
            description: this.translate.instant('lang.notifTour2Description'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'createScriptTour@administration/notifications/4',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.createScriptTourTitle')}</b>`,
            description: this.translate.instant('lang.createScriptTourDescription'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'notifTour@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.notifTourTitle')}</b>`,
            description: this.translate.instant('lang.notifTourDescription'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'notifTour3@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.notifTour3Title')}</b>`,
            description: this.translate.instant('lang.notifTour3Description'),
            redirectToAdmin: false,
        },
        {
            type: 'notification',
            stepId: 'notifTour4@administration/notifications',
            title: `<i class="far fa-question-circle" color="primary"></i>&nbsp;<b color="primary">${this.translate.instant('lang.notifTour4Title')}</b>`,
            description: this.translate.instant('lang.notifTour4Description'),
            redirectToAdmin: true,
        },
    ];

    featureTourEnd: any[] = [];

    constructor(
        private translate: TranslateService,
        private readonly joyrideService: JoyrideService,
        private localStorage: LocalStorageService,
        private headerService: HeaderService,
        private functionService: FunctionsService,
        private router: Router
    ) {
        this.getCurrentStepType();
    }

    init() {
        if (!this.functionService.empty(this.currentStepType)) {
            const steps = this.tour.filter(step => step.type === this.currentStepType).map(step => step.stepId);
            this.joyrideService.startTour(
                {
                    customTexts: {
                        next: '>>',
                        prev: '<<',
                        done: this.translate.instant('lang.getIt')
                    },
                    steps: steps,
                    waitingTime: 500
                }
            ).subscribe(
                step => {
                    /*Do something*/
                    this.currentTour = this.tour.filter((item: any) => item.stepId.split('@')[0] === step.name)[0];
                    const containerElement = document.getElementsByClassName('joyride-step__container') as HTMLCollectionOf<HTMLElement>
                    containerElement[0].style.width = 'auto';
                    containerElement[0].style.height = 'auto';
                    document.getElementsByClassName('joyride-step__header')[0].innerHTML = `${this.currentTour.title}`;
                    document.getElementsByClassName('joyride-step__body')[0].innerHTML = `${this.currentTour.description}`;
                },
                error => {
                    /*handle error*/
                },
                () => {
                    if (this.currentTour.redirectToAdmin) {
                        this.router.navigate(['/administration']);
                    }
                    this.endTour();
                }
            );
        }
    }

    getCurrentStepType() {
        if (this.localStorage.get(`featureTourEnd_${this.headerService.user.id}`) !== null) {
            this.featureTourEnd = JSON.parse(this.localStorage.get(`featureTourEnd_${this.headerService.user.id}`));
        }
        const unique = [...new Set(this.tour.map(item => item.type))];

        this.currentStepType = unique.filter(stepType => this.featureTourEnd.indexOf(stepType) === -1)[0];
    }

    endTour() {
        this.featureTourEnd.push(this.currentStepType);
        this.localStorage.save(`featureTourEnd_${this.headerService.user.id}`, JSON.stringify(this.featureTourEnd));
        this.getCurrentStepType();
    }

}
