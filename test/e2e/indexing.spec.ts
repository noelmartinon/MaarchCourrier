describe('Indexing document EE', () => {
  it('Record incoming mail', () => {
    cy.login();
    cy.visit('home')
    cy.wait(500)
    cy.get('#indexing')
      .click()
    cy.get('#doctype')
      .click()
    cy.wait(500)
    cy.get('[title="Demande de renseignements"]')
      .click({force: true});
    cy.get('#priority')
      .click()
    cy.get('[title="Normal"]')
      .click({force: true})
    cy.get('#documentDate')
      .click()
    cy.get('.mat-calendar-body-active')
      .click();
    cy.get('#subject')
      .type('test ee')
    cy.get('#senders')
      .type('pascon')
    cy.get('#senders-6')
      .click()
    cy.get('#destination')
      .click()
    cy.wait(500)
    cy.get('[title="Pôle Jeunesse et Sport"]')
      .click({force: true});
    cy.wait(500)
    cy.fixture('sample.pdf').then(fileContent => {
      cy.get('input[type="file"]').attachFile({
          fileContent: fileContent.toString(),
          fileName: 'sample.pdf',
          mimeType: 'application/pdf'
      });
    });
    cy.wait(500)
    cy.get('.mat-button-wrapper')
      .contains('Valider')
      .click()
    cy.wait(500)
    cy.get('[placeholder="Ajouter une annotation"]')
      .type('test ee')
    cy.get('.mat-dialog-content-container .mat-button-wrapper')
      .contains('Valider')
      .click()
    cy.wait(1000)
    cy.url().should('include', '/resources/')
  })
})