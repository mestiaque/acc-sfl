ac_Branch 
    name, code, is_actime, location, branch head
ac_payment Method
    Name, is_active
ac_Accounts
    name, employee id, designation, user_id(forgain key form user table), balance, branch_id (from branch), is_active
ac_Master Particular
    Name, desciption, is_active, debit(0,1), credit(0,1)

ac_Particular 
    Name, code, is_active, description

ac_balance_recive 
    recive no, date, particular_id, description, branch_id etc,

ac_Expenses 
    date, Payment Method, branch_id,account_id, dateexpense no, perticular_id, invoice, qty, uom, rate, amount, description, branch_id, company_name, Receiver Name, receiver_mobile, attachment,

ac_expense iou
    iou no, account_id, date, adjust date, Payment Method, branch_id perticular_id/ids, amount, desciptionm, Receiver Name, receiver_mobile, employee_id

ac_transactions
    id, date, opennign balance, transaction type, transaction id, desciption, amount, balance, 
