<?php

$I = new AcceptanceTester($scenario);
initTest::login($I);
$I->amOnPage('/admin/components/run/shop/currencies/create');
$I->fillField(CurrenciesPage::$NameCurrencyCreate, 'qййййй1234');
$I->fillField(CurrenciesPage::$IsoCodCreate, 'qййййй1234');
$I->fillField(CurrenciesPage::$SymbolCreate, 'qййййй1234');
$I->fillField(CurrenciesPage::$Rate, '111112.1233');
$I->click(CurrenciesPage::$SaveButton);
$I->waitForElementVisible('.alert.in.fade.alert-error');
$I->see('Поле Iso Код не может превышать 5 символов в длину.');
$I->see('Поле Символ не может превышать 5 символов в длину.');
$I->waitForElementNotVisible('.alert.in.fade.alert-error');
$I->fillField(CurrenciesPage::$IsoCodCreate, 'qйййй');
$I->click(CurrenciesPage::$SaveButton);
$I->waitForElementVisible('.alert.in.fade.alert-error');
$I->see('Поле Символ не может превышать 5 символов в длину.');
$I->waitForElementNotVisible('.alert.in.fade.alert-error');
$I->fillField(CurrenciesPage::$IsoCodCreate, 'qййййй1234');
$I->fillField(CurrenciesPage::$SymbolCreate, 'qйййй');
$I->click(CurrenciesPage::$SaveButton);
$I->waitForElementVisible('.alert.in.fade.alert-error');
$I->see('Поле Iso Код не может превышать 5 символов в длину.');
$I->waitForElementNotVisible('.alert.in.fade.alert-error');
$I->fillField(CurrenciesPage::$IsoCodCreate, 'qйййй');
$I->click(CurrenciesPage::$SaveButton);
$I->waitForElementVisible('.alert.in.fade.alert-success');
$I->see('Валюта создана');
$I->waitForElementNotVisible('.alert.in.fade.alert-success');
$I->waitForText('Редактирование валют');
$I->seeInField(CurrenciesPage::$NameCurrencyEdit, 'qййййй1234');
$I->seeInField(CurrenciesPage::$IsoCodEdit, 'qйййй');
$I->seeInField(CurrenciesPage::$SymbolEdit, 'qйййй');
$I->seeInField(CurrenciesPage::$Rate, '111112.1233');      