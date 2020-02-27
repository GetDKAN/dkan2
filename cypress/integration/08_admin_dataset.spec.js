context('Admin dataset view', () => {

    beforeEach(() => {
        cy.drupalLogin('admin', 'admin')
        cy.visit("http://dkan/admin/content/datasets")
    })
    
    it('There is an "Add new dataset" button that takes user to the dataset json form. And a "Back to Datasets" button that returns user to the datasets view.', () => {
        cy.get('h1').should('have.text', 'Datasets')
        cy.get('.view-header > .button').should('contain', 'Add new dataset').click({ force:true })
        cy.get('#app > button.btn-default').should('contain', 'Back to Datasets').click({ force:true })
        cy.get('h1').should('have.text', 'Datasets')
    })

    it('The dataset data node titles should link to the REACT page. The edit link should go to the json form.', () => {
        cy.get('tbody > :nth-child(1) > .views-field-title > a').invoke('attr', 'href').should('contain', '/dataset/')
        cy.get('tbody > :nth-child(1) > .views-field-nothing > a').invoke('attr', 'href').should('contain', 'admin/dkan/dataset?id=');
    })

})